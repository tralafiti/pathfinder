# *Docker*

## Todo
* documentation / howto
* automate creation of environment settings
    * redis is auto-configured already (conf/docker.ini)
    * make it possible to overwrite individual values of environment.ini
        * auto-set db dns, name, user and pass
* maybe split up php monster-container into php, socket and cron
* pimp nginx config
* dist-files for vhost etc.
* move index.php into public and only serve this folder
* no more root

## WORK IN PROGRESS...

### Basic setup
* copy `.env.dist` to `.env` and fill out
* copy `app/environment.ini` to `conf/environment.ini`
* activate docker-db
``` 
DB_PF_DNS                   =   mysql:host=db;port=3306;dbname=
DB_PF_NAME                  =   pathfinder
DB_PF_USER                  =   root
DB_PF_PASS                  =

DB_UNIVERSE_DNS             =   mysql:host=db;port=3306;dbname=
DB_UNIVERSE_NAME            =   eve_universe
DB_UNIVERSE_USER            =   root
DB_UNIVERSE_PASS            =
```
* optionally active websocket
```
SOCKET_HOST                 =   php
SOCKET_PORT                 =   5555
```
* add other settings for sso etc.
* copy `docker/nginx/vhost.conf.dist` to `docker/nginx/vhost.conf`
    * modify `server_name` to fit your url (pathfinder uses this information for redirects)
    * optionally enable basic auth protection of the /setup-route:
      * comment in location block
      * place .htpasswd file in auth-folder
* install php dependencies
```bash
docker-compose exec -u www-data php composer install -o
```

### Database seeding

* goto `/setup`
* for all databases
    * click "create database"
    * click "setup tables"
    * click " fix columns/keys"`
* import data (replace *${PASSWORD}*)
    * `unzip -p export/sql/eve_universe.sql.zip | docker container exec -i $(docker-compose ps -q db) mysql -p${PASSWORD} eve_universe`
* build index
    * scroll all the way down to "Administration" / "Index data"
    * click all the buttons!!
    
### SSL

* Nginx comes with a self-signed cert for the CN `docker.local`
* -> `https://docker.local` should work out of the box (point `docker.local` to your ip via host-file e.g.)

### Development

Gulp watcher
```bash
docker-compose -f docker-compose-dev.yml run --rm node npm run gulp
```

Gulp build
```bash
docker-compose -f docker-compose-dev.yml run --rm node npm run gulp production
```

Start / Stop Adminder DB Interface
```bash
docker-compose -f docker-compose.yml -f docker-compose-dev.yml up -d adminer
```
```bash
docker-compose -f docker-compose-dev.yml rm -s adminer
```

Backup / restore DB
```
docker container exec -i $(docker-compose ps -q db) mysqldump -p${PASSWORD} pathfinder > pathfinder.sql
```

```bash
cat pathfinder.sql | docker container exec -i $(docker-compose ps -q db) mysql -p${PASSWORD} pathfinder
```