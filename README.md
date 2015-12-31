# fol-core

[![Build Status](https://travis-ci.org/fol-project/core.svg?branch=master)](https://travis-ci.org/fol-project/core)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fol-project/core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fol-project/core/?branch=master)

Trátase dunha simple clase que serve de contenedor da nosa app. Contén as seguintes funcionalidades:

## Container-interop

Compatible coa interface [container-interop](https://github.com/container-interop/container-interop), o que permite interactuar con outros contedores. Podes especificar dependencias ou engadir outros contenedores:

```php
$app = new Fol();

//Definir dependencias:
$app->set('database', function () {
    return new MyDatabaseClass($config);
});

//Engadir outros conenedores compatibles con Container-Interop
$app->add($container);

//Engadir un ServiceProviderInterface
$app->register(new MyServiceProvider());

//Obter as dependencias
$database = $app->get('database');

//Tamén podes usar a interface de array para engadir/obter dependencias:
$database = $app['database'];

$app['templates'] = function () {
    return new TemplatesEngine();
};
```

## Path

A parte de servir de container, tamén serve para definir o path da nosa aplicación. O path é simplemente a ruta absoluta ao directorio da aplicación:

```php
$app = new Fol();

//Dame a ruta
$app->getPath(); // /var/www/sitioweb/app

//Dame a ruta xuntándolle estas pezas:
$app->getPath('dir/subdir', '../outro'); // /var/www/sitioweb/dir/outro

//O path calculase automaticamente (o directorio onde se atopa a clase instanciada) pero podes cambialo:
$path->setPath(__DIR__); //Nunca pode rematar en "/"
```

## Url

Outra función é gardar a url pública dende a que se accede á nosa app, útil para xerar links, por exemplo:

```php
$app = new Fol();

//Define unha url
$app->setUrl('http://localhost/o-meu-sitio');

//Dame a url
$app->getUrl(); // http://localhost/o-meu-sitio

//Dame só o path
$app->getUrlPath(); // /o-meu-sitio

//Dame só o host
$app->getUrlHost(); // http://localhost

//Tamén podes engadirlle pezas:
$app->getUrl('post/1', 'ver'); // http://localhost/o-meu-sitio/post/1/ver

$app->getUrlPath('post/1', 'ver'); // /o-meu-sitio/post/1/ver
```

## Namespace

Por último, temos unha utilidade para devolver o namespace da app. Útil para instanciar clases relativas.

```php
namespace App;

use Fol;

class App extends Fol {
    
}

$app = new App();

//Dame o namespace
$app->getNamespace(); // App

//Tamén podes engadirlle pezas
$app->getNamespace('Controllers\\Base'); // App\\Controllers\\Base;
```