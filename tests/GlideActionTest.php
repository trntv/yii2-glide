<?php

namespace trntv\glide_tests;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideActionTest extends TestCase
{
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
        $rigthSignedUrl = '/index.php?r=glide%2Findex&path=test-img&s=7632784193f00c91a6f06dc87c43090f';
        $wrongSignedUrl = '/index.php?r=glide%2Findex&path=test-img&w=1000000&s=7632784193f00c91a6f06dc87c43090f';
        $this->assertEquals(
            true,
            $this->getGlideAction()->validateRequest(Request::create($rigthSignedUrl))
        );
        $this->assertEquals(
            false,
            $this->getGlideAction()->validateRequest(Request::create($wrongSignedUrl))
        );
    }

    public function testRequestValidationPrettyUrl()
    {
        \Yii::$app->urlManager->enablePrettyUrl = true;
        \Yii::$app->urlManager->showScriptName = false;
        $rigthSignedUrl = '/glide/index?path=test-img&s=d60ed7390b035237c96135e76038b7e4';
        $wrongSignedUrl = '/glide/index?path=test-img&w=10000&s=d60ed7390b035237c96135e76038b7e4';
        $this->assertEquals(
            true,
            $this->getGlideAction()->validateRequest(Request::create($rigthSignedUrl))
        );
        $this->assertEquals(
            false,
            $this->getGlideAction()->validateRequest(Request::create($wrongSignedUrl))
        );
    }
}

