<?php

namespace trntv\glide_tests;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideActionTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testImageFound()
    {
        \Yii::$app->glide->signKey = null;
        ob_start();
        $this->getGlideAction()->run('kayaks.jpg');
        ob_end_clean();
    }

    public function testImageNotFound()
    {
        \Yii::$app->glide->signKey = null;
        $this->setExpectedException('\yii\web\NotFoundHttpException');
        $this->getGlideAction()->run('wrong-image.jpg');
    }

    public function testRequestValidationUglyUrl()
    {
        $rightSignedUrl = '/index.php?r=glide%2Findex&path=test-img&s=b3a54d71e6c6a61149325ef556d1d55b';
        $wrongSignedUrl = '/index.php?r=glide%2Findex&path=test-img&w=1000&s=b3a54d71e6c6a61149325ef556d1d55b';

        $this->assertTrue($this->getGlideAction()->validateRequest(Request::create($rightSignedUrl)));
        $this->assertFalse($this->getGlideAction()->validateRequest(Request::create($wrongSignedUrl)));
    }

    public function testRequestValidationPrettyUrl()
    {
        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;
        $rightSignedUrl = '/glide/index?path=test-img&s=b9162dbcf5705d7ac929b692f20320b0';
        $wrongSignedUrl = '/glide/index?path=test-img&w=1000&s=b9162dbcf5705d7ac929b692f20320b0';

        $this->assertTrue($this->getGlideAction()->validateRequest(Request::create($rightSignedUrl)));
        $this->assertFalse($this->getGlideAction()->validateRequest(Request::create($wrongSignedUrl)));
    }
}

