Yii2 Glide
==========
Yii2 Glide integration.
> Glide is a wonderfully easy on-demand image manipulation library written in PHP.

Before you start read [Glide documentation](http://glide.thephpleague.com/) to understand what we are doing

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist trntv/yii2-glide "*"
```

or add

```
"trntv/yii2-glide": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Add glide configuration:

```php
'components' => [
    ...
    'glide' => [
        'class' => 'trntv\glide\components\Glide',
        'sourcePath' => '@app/web/uploads',
        'cachePath' => '@runtime/glide',
    ],
    ...
]
```

Then you can output modified image like so:
```php
Yii::$app->glide->outputImage('new-upload.jpg', ['w' => 100, 'fit' => 'crop'])
```

You can also use ``trntv\glide\actions\GlideAction`` to output images:
In any controller add (``SiteController`` for example):
```php
public function actions()
{
    return [
        'glide' => 'trntv\glide\actions\GlideAction'
    ]
}
```
Than use it:
``/index.php?r=site/glide?path=new-upload.jpg&w=100&h=75``

Secure Urls
-----------
TBD