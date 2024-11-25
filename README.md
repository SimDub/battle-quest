# lancer le projet en mode debug :
```bash
XDEBUG_MODE=debug XDEBUG_SESSION=1 docker compose up --wait
```

# accéder à l'api :
```bash
https://localhost/docs/
```

# accéder à la pwa :
```bash
https://localhost/
```

# accéder à l'admin :
```bash
http://localhost/admin/
```

# lancer les tests :

## avec xdebug :
```bash
docker compose exec php php -dxdebug.start_with_request=yes bin/phpunit
```

## sans xdebug :
```bash
docker compose exec php php bin/phpunit
```
# lien vers le projet :
https://github.com/SimDub/battle-quest