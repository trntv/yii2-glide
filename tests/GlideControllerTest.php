<?php

namespace trntv\glide_tests;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideControllerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testIndexActionWorks()
    {
        \Yii::$app->glide->signKey = null;
        ob_start();
        \Yii::$app->runAction('glide/index', ['path' => 'kayaks.jpg']);
        ob_end_clean();
    }
}

