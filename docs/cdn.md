CDN nginx config

```nginx
server {
        listen 80;
        listen [::]:80;
        server_name cdn-cf1.keeperfx.net cdn-b1.keeperfx.net;

        access_log off;
        error_log off;

        return 301 https://$host$request_uri;
}

server {

        # Listen - HTTP/2 (and HTTP/1)
        listen 443 ssl;
        listen [::]:443 ssl;

        # Listen - HTTP/3
        # 'reuseport' can only be declared once and is already declared in keeperfx.net.conf
        #listen 443 quic;
        #listen [::]:443 quic;

        # Hostname
        server_name cdn-cf1.keeperfx.net cdn-b1.keeperfx.net;

        # Allow main domain to request resources (CORS)
        set $cors_origin "*";
        # add_header 'Access-Control-Allow-Origin' 'https://keeperfx.net';
        # add_header 'Access-Control-Allow-Origin' '*';

        # Proxy forwarding headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;

        # Root path message
        location = / {
                default_type text/plain;
                return 200 "KeeperFX CDN";
        }

        # Only allow assets and download endpoints
        location ~ ^/(?!workshop/download/|download/|assets/|game-files/download/|speedtest/) {
                return 404;
        }

        # Asset Caching
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|woff|woff2|ttf|otf|svg|svgz|webp)$ {
                proxy_pass http://127.0.0.1:5500;
                proxy_cache asset_cache;
                proxy_cache_valid 200 7d;
                proxy_cache_bypass $http_cache_control;
                add_header X-Proxy-Cache $upstream_cache_status;
                add_header Alt-Svc 'h3=":443"; ma=86400' always;
                add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header Access-Control-Allow-Origin $cors_origin always;
                expires 31d;
        }

        # Download caching
        location ~* \.(7z|rar|zip|tar|gz|bz2|exe)$ {
                proxy_pass http://127.0.0.1:5500;
                proxy_buffer_size 1M;
                proxy_buffers 32 1M;
                proxy_busy_buffers_size 2M;
                proxy_cache asset_cache;
                proxy_cache_valid 200 3d;
                proxy_cache_bypass $http_cache_control;
                add_header X-Proxy-Cache $upstream_cache_status;
                add_header Alt-Svc 'h3=":443"; ma=86400' always;
                add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header Access-Control-Allow-Origin $cors_origin always;
                expires 3d;
        }

        location ~ ^/game-files/download/([a-z]+)/([0-9\.]+)/(.+)$ {
                proxy_pass http://127.0.0.1:5500;
                proxy_buffer_size 1M;
                proxy_buffers 32 1M;
                proxy_busy_buffers_size 2M;
                proxy_cache asset_cache;
                proxy_cache_valid 200 3d;
                proxy_cache_bypass $http_cache_control;
                add_header X-Proxy-Cache $upstream_cache_status;
                add_header Alt-Svc 'h3=":443"; ma=86400' always;
                add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header Access-Control-Allow-Origin $cors_origin always;
                expires 3d;
        }

        # Speedtest files
        location ~ ^/speedtest/(.+)$ {
                proxy_pass http://127.0.0.1:5500;
                proxy_buffer_size 1M;
                proxy_buffers 32 1M;
                proxy_busy_buffers_size 2M;
                proxy_cache asset_cache;
                proxy_cache_valid 200 3d;
                proxy_cache_bypass $http_cache_control;
                add_header X-Proxy-Cache $upstream_cache_status;
        }

        # SSL certs
        ssl_certificate          /etc/letsencrypt/live/keeperfx.net/fullchain.pem;
        ssl_certificate_key      /etc/letsencrypt/live/keeperfx.net/privkey.pem;
        ssl_trusted_certificate  /etc/letsencrypt/live/keeperfx.net/fullchain.pem;

        # Enable SSL stapling
        ssl_stapling on;
        ssl_stapling_verify on;

        ssl_protocols TLSv1.3;
        ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';
        #ssl_ciphers 'TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256';
        #ssl_ciphers 'TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_128_GCM_SHA256';
        ssl_prefer_server_ciphers on;
        ssl_session_cache shared:SSL:50m;
        ssl_session_timeout 1d;
        ssl_session_tickets off;

        # SSL protocol settings
        #ssl_prefer_server_ciphers   on;
        #ssl_protocols               TLSv1 TLSv1.1 TLSv1.2;  
        #ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384';
}



```
