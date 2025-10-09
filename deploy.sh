#!/bin/bash

# SSO PIPP Production Deployment Script
# This script handles the deployment of the SSO PIPP application to production

set -e  # Exit on any error

# Configuration
APP_NAME="sso-pipp"
DOCKER_COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env.production"
BACKUP_DIR="./backups"
LOG_FILE="./deploy.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root for security reasons"
        exit 1
    fi
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check if Docker is installed and running
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
        exit 1
    fi
    
    if ! docker info &> /dev/null; then
        error "Docker is not running"
        exit 1
    fi
    
    # Check if Docker Compose is installed
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed"
        exit 1
    fi
    
    # Check if required files exist
    if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
        error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
        exit 1
    fi
    
    if [[ ! -f "$ENV_FILE" ]]; then
        error "Environment file not found: $ENV_FILE"
        exit 1
    fi
    
    success "Prerequisites check passed"
}

# Create backup directory
create_backup_dir() {
    if [[ ! -d "$BACKUP_DIR" ]]; then
        mkdir -p "$BACKUP_DIR"
        log "Created backup directory: $BACKUP_DIR"
    fi
}

# Backup database
backup_database() {
    log "Creating database backup..."
    
    local backup_file="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    # Get database credentials from environment file
    local db_host=$(grep "^DB_HOST=" "$ENV_FILE" | cut -d'=' -f2)
    local db_name=$(grep "^DB_DATABASE=" "$ENV_FILE" | cut -d'=' -f2)
    local db_user=$(grep "^DB_USERNAME=" "$ENV_FILE" | cut -d'=' -f2)
    
    if docker-compose exec -T postgres pg_dump -h "$db_host" -U "$db_user" -d "$db_name" > "$backup_file" 2>/dev/null; then
        success "Database backup created: $backup_file"
    else
        warning "Database backup failed or database not running"
    fi
}

# Health check function
health_check() {
    log "Performing health check..."
    
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s http://localhost/api/health > /dev/null 2>&1; then
            success "Health check passed"
            return 0
        fi
        
        log "Health check attempt $attempt/$max_attempts failed, waiting 10 seconds..."
        sleep 10
        ((attempt++))
    done
    
    error "Health check failed after $max_attempts attempts"
    return 1
}

# Rollback function
rollback() {
    error "Deployment failed, initiating rollback..."
    
    # Stop current containers
    docker-compose down
    
    # Restore from backup if available
    local latest_backup=$(ls -t "$BACKUP_DIR"/db_backup_*.sql 2>/dev/null | head -n1)
    if [[ -n "$latest_backup" ]]; then
        log "Restoring database from backup: $latest_backup"
        # Add database restore logic here
    fi
    
    # Start previous version (this would need to be implemented based on your versioning strategy)
    warning "Manual intervention may be required to restore previous version"
}

# Main deployment function
deploy() {
    log "Starting deployment of $APP_NAME..."
    
    # Copy production environment file
    if [[ -f "$ENV_FILE" ]]; then
        cp "$ENV_FILE" .env
        log "Production environment file copied"
    fi
    
    # Pull latest images
    log "Pulling latest Docker images..."
    docker-compose pull
    
    # Build application image
    log "Building application image..."
    docker-compose build --no-cache app
    
    # Stop existing containers
    log "Stopping existing containers..."
    docker-compose down
    
    # Start new containers
    log "Starting new containers..."
    docker-compose up -d
    
    # Wait for containers to be ready
    log "Waiting for containers to start..."
    sleep 30
    
    # Run database migrations
    log "Running database migrations..."
    if ! docker-compose exec -T app php artisan migrate --force; then
        error "Database migration failed"
        rollback
        exit 1
    fi
    
    # Clear and cache configuration
    log "Optimizing application..."
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    
    # Perform health check
    if ! health_check; then
        rollback
        exit 1
    fi
    
    success "Deployment completed successfully!"
}

# Cleanup old backups
cleanup_backups() {
    log "Cleaning up old backups..."
    find "$BACKUP_DIR" -name "db_backup_*.sql" -mtime +7 -delete
    success "Old backups cleaned up"
}

# Show usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -b, --backup   Create backup only"
    echo "  -d, --deploy   Deploy application"
    echo "  -r, --rollback Rollback deployment"
    echo "  -c, --cleanup  Cleanup old backups"
    echo "  -s, --status   Show application status"
}

# Show application status
show_status() {
    log "Application Status:"
    docker-compose ps
    
    log "Container Logs (last 20 lines):"
    docker-compose logs --tail=20
    
    log "Health Check:"
    if curl -f -s http://localhost/api/health; then
        success "Application is healthy"
    else
        error "Application health check failed"
    fi
}

# Main script logic
main() {
    case "${1:-}" in
        -h|--help)
            usage
            exit 0
            ;;
        -b|--backup)
            check_prerequisites
            create_backup_dir
            backup_database
            ;;
        -d|--deploy)
            check_root
            check_prerequisites
            create_backup_dir
            backup_database
            deploy
            cleanup_backups
            ;;
        -r|--rollback)
            rollback
            ;;
        -c|--cleanup)
            cleanup_backups
            ;;
        -s|--status)
            show_status
            ;;
        "")
            log "No option specified. Use -h for help."
            usage
            exit 1
            ;;
        *)
            error "Unknown option: $1"
            usage
            exit 1
            ;;
    esac
}

# Trap errors and perform cleanup
trap 'error "Script interrupted"; exit 1' INT TERM

# Run main function
main "$@"