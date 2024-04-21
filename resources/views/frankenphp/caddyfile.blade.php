{
	frankenphp
	order php_server before file_server
}

# The domain name of your server
localhost {
	# Set the webroot to the public/ directory
	root * dist/app/public/
	# Enable compression (optional)
	encode zstd br gzip
	# Execute PHP files from the public/ directory and serve assets
	php_server
}