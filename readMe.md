
### Les pré-requis techniques
    PHP 8.1.0 ou plus 

# 1) Installation

Après un checkout il est nécessaire de procéder à certaines opérations pour que l'application soit fonctionnelle.

### Démarrage des services
    git clone https://github.com/oussama-aitmi/symfony-vue
    docker-compose up -d
    visite  http://demo-proxy.localhost/ pour voir le dashboard traefik 

### Accedez à votre conteneur 
    docker exec -it demo-app sh 

### Edition de fichier de l'environement pour configurer votre base de donner
    vim .env
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate

### Excuter les fixtures
    php bin/console doctrine:fixtures:load

### installer les dépendances Php
    composer install

### Application
    http://demo.localhost
