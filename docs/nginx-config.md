KeeperFX Nginx Configuration
============================

This is an example nginx config for the KeeperFX website application. Edit as needed.
Be sure to point the `root` variable below to the `/public` directory.

All non static files should be routed trough `index.php`.

```nginx
server {
	listen       127.0.0.1:80;
	server_name  keeperfx.local;
	
	root   /var/www/keeperfx-website/public;
	index  index.php;

	location / {
		try_files $uri /index.php$is_args$args;
	}
	
	location ~ \.php$ {
        
        # Linux
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;

        # Windows
		# fastcgi_pass   127.0.0.1:9000;
		# fastcgi_index  index.php;
		# fastcgi_param  SCRIPT_FILENAME  $document_root/$fastcgi_script_name;
		# include        fastcgi_params;
	}
}
```

## Alpha build serving

If you use the env var `KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH` you can setup nginx to serve those files using any chosen directory.

```nginx
location /downloads/ {
    alias /var/www/keeperfx-website/keeperfx-alpha-builds/;
}
```

**NOTE:** When using `alias` be sure to add trailing directory slashes.

***Symlinks** can also be use but are not recommended.*
