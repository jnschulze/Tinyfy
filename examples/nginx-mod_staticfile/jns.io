server {
    listen 80;
    server_name ~^assets\d+.jns.io$;
    root /var/www/jns.io/static;
    charset utf-8;
    access_log off;

    location ~* \.css|js$ {
        gzip_static on;
        gzip_vary on;
        expires max;
        etag off;
        add_header Cache-Control public;
        rewrite  "^/c/(.{32}).*.css$" /_compiled/$1.css last;
        rewrite  "^/c/(.{32}).*.js$"  /_compiled/$1.js last;
    }

    location ~* \.(eot|ttf|woff)$ {
        add_header Access-Control-Allow-Origin *;
    }
}
