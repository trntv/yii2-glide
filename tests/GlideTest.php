<?php

namespace trntv\glide_tests;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideTest extends TestCase
{

    public function testSignedUrl()
    {
        $rigthSignedUrl = '/index.php?r=glide%2Findex&path=test-img&s=7632784193f00c91a6f06dc87c43090f';
        $signedUrl = \Yii::$app->glide->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rigthSignedUrl, $signedUrl);

        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;
        $rigthSignedUrl = '/glide/index?path=test-img&s=d60ed7390b035237c96135e76038b7e4';
        $signedUrl = \Yii::$app->glide->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rigthSignedUrl, $signedUrl);
    }


}
