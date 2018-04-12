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

### Env-Settings (to be automated)
* copy `.env.dist` to `.env` and fill out
* copy `app/environment.ini` to `conf/environment.ini`
* to use docker-db (password missing)
``` 
DB_PF_DNS                   =   mysql:host=db;port=3306;dbname=
DB_PF_NAME                  =   pathfinder
DB_PF_USER                  =   root

DB_UNIVERSE_DNS             =   mysql:host=db;port=3306;dbname=
DB_UNIVERSE_NAME            =   eve_universe
DB_UNIVERSE_USER            =   root

DB_CCP_DNS                  =   mysql:host=db;port=3306;dbname=
DB_CCP_NAME                 =   eve_static
DB_CCP_USER                 =   root
```
* for websocket
```
SOCKET_HOST                 =   php
SOCKET_PORT                 =   5555
```
* add other settings for sso etc.

### Database seeding

* goto `/setup`
* for all three databases
    * click "create database"
* for "Pathfinder" and "EVE-Online universe"
    * click "setup tables"
    * click " fix columns/keys"`
* import data (replace *${PASSWORD}*)
    * `docker-compose exec -T db mysql -p${PASSWORD} pathfinder < export/sql/pathfinder.sql`
    * `unzip -p export/sql/eve_lifeblood_min.sql.zip | docker-compose exec -T db mysql -p${PASSWORD} eve_static`

### SSL

* Nginx comes with a self-signed cert for the CN `docker.local`
* -> `https://docker.local` should work out of the box (point `docker.local` to your ip via host-file e.g.)
