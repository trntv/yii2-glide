<?php

namespace trntv\glide\actions;

use Symfony\Component\HttpFoundation\Request;
use Yii;
use yii\base\Action;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class GlideAction extends Action
{
    /**
     * @var string
     */
    public $component = 'glide';

    /**
     * @param $path
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws NotSupportedException
     */
    public function run($path)
    {
        if (!$this->getServer()->sourceFileExists($path)) {
            throw new NotFoundHttpException;
        }

        if ($this->getComponent()->signKey) {
            $request = Request::create(Yii::$app->request->getUrl());
            if (!$this->validateRequest($request)) {
                throw new BadRequestHttpException('Wrong signature');
            };
        }

        try {
            $this->getServer()->outputImage($path, Yii::$app->request->get());
        } catch (\Exception $e) {
            throw new NotSupportedException($e->getMessage());
        }
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
    public function getComponent()
    {
        return Yii::$app->get($this->component);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function validateRequest(Request $request)
    {
        return $this->getComponent()->validateRequest($request);
    }
}
