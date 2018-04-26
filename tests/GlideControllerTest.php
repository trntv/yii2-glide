<?php

namespace trntv\glide_tests;

use yii\helpers\Url;
use yii\web\Response;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideControllerTest extends TestCase
{
    public function testIndexActionWorks()
    {
        \Yii::$app->glide->signKey = null;
        /** @var Response $response */
        $response = \Yii::$app->runAction('glide/index', ['path' => 'kayaks.jpg']);
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testSignatureGeneration()
    {
        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;

        $signature = $this->getGlide()->getHttpSignature()->generateSignature('/glide/index', ['path' => 'kayaks.jpg']);
        $url = Url::to(['/glide/index', 'path' => 'kayaks.jpg', 's' => $signature]);
        \Yii::$app->request->setUrl($url);
        $response = \Yii::$app->runAction('glide/index', ['path' => 'kayaks.jpg', 's' => $signature]);
        $this->assertEquals($response->getStatusCode(), 200);
    }
}

