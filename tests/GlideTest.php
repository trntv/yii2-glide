<?php

namespace trntv\glide_tests;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideTest extends TestCase
{

    public function testCreateSignedUrl()
    {
        $rightSignedUrl = '/index.php?r=glide%2Findex&path=test-img&s=b3a54d71e6c6a61149325ef556d1d55b';
        $signedUrl = $this->getGlide()->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rightSignedUrl, $signedUrl);

        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;
        $rightSignedUrl = '/glide/index?path=test-img&s=b9162dbcf5705d7ac929b692f20320b0';
        $signedUrl = $this->getGlide()->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rightSignedUrl, $signedUrl);
    }

    public function testSignUrl()
    {
        $url = 'https://www.google.com.ua/images/srpr/logo11w.png';
        $rightSignedUrl = $url . '?w=100&s=d963f7d8b0d14afaa12b1679042ebad4';
        $this->assertEquals(
            $rightSignedUrl,
            $this->getGlide()->signUrl($url, ['w' => 100])
        );
    }


    /**
     * @return \trntv\glide\components\Glide;
     */
    protected function getGlide()
    {
        return \Yii::$app->get('glide');
    }

}
