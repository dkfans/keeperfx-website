## WIP Docker documentation


$ cp .env.docker .env

$ docker-compose up
$ docker exec kfx-php php console migrations:migrate
$ docker exec kfx-php php console user:create <username> <password> admin

For local storage or development:
$ mkdir -p \
	./storage/workshop \
	./storage/upload \
	./storage/alpha-patch \
	./storage/alpha-patch-file-bundle \
	./storage/prototype-file-bundle \
	./storage/prototype \
	./storage/crash-report \
	./storage/news-img \
	./storage/avatar
$ chmod -R 777 ./storage


On production we use webdav links for the volume instead:



