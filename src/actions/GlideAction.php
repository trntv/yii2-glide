<?php

namespace trntv\glide\actions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yii;
use yii\base\Action;
use yii\web\BadRequestHttpException;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideAction extends Action
{
    public $component = 'glide';
    public function run()
    {
        $request = Request::createFromGlobals();

        if (!$this->getServer()->sourceFileExists($request)) {
            throw new NotFoundHttpException;
        }

        if (!$this->getComponent()->validateRequest($request)) {
            throw new BadRequestHttpException;
        };
        $this->getServer()->outputImage($request->get('file'), $request);
    }

    /**
     * @return \League\Glide\Server
     */
    protected function getServer()
    {
        return $this->getComponent()->getServer();
    }

    /**
     * @return \trntv\glide\components\Glide;
     */
    protected function getComponent()
    {
        return Yii::$app->getComponents($this->component);
    }
}
