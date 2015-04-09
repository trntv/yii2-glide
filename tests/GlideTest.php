<?php

namespace trntv\glide_tests;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideTest extends TestCase
{

    public function testCreateSignedUrl()
    {
        $rigthSignedUrl = '/index.php?r=glide%2Findex&path=test-img&s=7632784193f00c91a6f06dc87c43090f';
        $signedUrl = $this->getGlide()->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rigthSignedUrl, $signedUrl);

        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;
        $rigthSignedUrl = '/glide/index?path=test-img&s=d60ed7390b035237c96135e76038b7e4';
        $signedUrl = $this->getGlide()->createSignedUrl(['glide/index', 'path' => 'test-img']);
        $this->assertEquals($rigthSignedUrl, $signedUrl);
    }

    public function testSignUrl()
    {
        $rightSignedUrl = 'https://www.google.com.ua/images/srpr/logo11w.png?w=100&s=a5571ac8f168b556c67cc38cc0aaba87';
        $url = 'https://www.google.com.ua/images/srpr/logo11w.png';
        $this->assertEquals(
            $rightSignedUrl,
            $this->$this->getGlide()->signUrl($url, ['w' => 100])
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
