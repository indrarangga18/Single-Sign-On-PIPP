#!/usr/bin/env python3
"""
Custom HTTP Server untuk Single Page Application (SPA)
Mendukung client-side routing dengan redirect semua route ke index.html
"""

import http.server
import socketserver
import os
import mimetypes
from urllib.parse import urlparse

class SPAHandler(http.server.SimpleHTTPRequestHandler):
    """Handler khusus untuk SPA yang mendukung client-side routing"""
    def end_headers(self):
        self.send_header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        self.send_header('Pragma', 'no-cache')
        super().end_headers()
    
    def do_GET(self):
        """Handle GET requests dengan fallback ke index.html untuk client-side routing"""
        
        # Parse URL path
        parsed_path = urlparse(self.path)
        path = parsed_path.path
        
        # Jika path adalah root, serve index.html
        if path == '/':
            path = '/index.html'
        
        # Buat full path ke file
        full_path = os.path.join(os.getcwd(), path.lstrip('/'))
        
        # Jika file ada, serve file tersebut
        if os.path.isfile(full_path):
            return super().do_GET()
        
        # Jika path adalah direktori dan ada index.html di dalamnya
        if os.path.isdir(full_path):
            index_path = os.path.join(full_path, 'index.html')
            if os.path.isfile(index_path):
                self.path = '/' + os.path.relpath(index_path, os.getcwd()).replace('\\', '/')
                return super().do_GET()
        
        # Jika file tidak ditemukan dan bukan request untuk asset (css, js, images)
        # redirect ke index.html untuk client-side routing
        if not any(path.endswith(ext) for ext in ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot']):
            self.path = '/index.html'
            return super().do_GET()
        
        # Untuk asset yang tidak ditemukan, return 404
        self.send_error(404, "File not found")
    
    def log_message(self, format, *args):
        """Custom log format"""
        print(f"[{self.address_string()}] {format % args}")

def run_server(port=8000):
    """Menjalankan server SPA"""
    
    # Pastikan kita berada di direktori public
    if not os.path.exists('index.html'):
        print("Error: index.html tidak ditemukan di direktori saat ini")
        print("Pastikan Anda menjalankan server dari direktori public/")
        return
    
    try:
        with socketserver.TCPServer(("", port), SPAHandler) as httpd:
            print(f"🚀 Server SPA berjalan di http://localhost:{port}")
            print(f"📁 Serving files dari: {os.getcwd()}")
            print("📋 Routes yang tersedia:")
            print(f"   • http://localhost:{port}/")
            print(f"   • http://localhost:{port}/login")
            print(f"   • http://localhost:{port}/dashboard")
            print("\n⚡ Client-side routing aktif - semua route akan diarahkan ke index.html")
            print("🛑 Tekan Ctrl+C untuk menghentikan server\n")
            
            httpd.serve_forever()
            
    except KeyboardInterrupt:
        print("\n🛑 Server dihentikan")
    except OSError as e:
        if e.errno == 48:  # Address already in use
            print(f"❌ Error: Port {port} sudah digunakan")
            print("💡 Coba gunakan port lain atau hentikan proses yang menggunakan port tersebut")
        else:
            print(f"❌ Error: {e}")

if __name__ == "__main__":
    import sys
    
    # Default port
    port = 8000
    
    # Jika ada argument port
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
        except ValueError:
            print("❌ Error: Port harus berupa angka")
            sys.exit(1)
    
    run_server(port)