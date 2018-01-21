<?php

namespace trntv\glide\actions;

use Symfony\Component\HttpFoundation\Request;
use Yii;
use yii\base\Action;
use yii\web\Response;
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
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function run($path)
    {
        if (!$this->getServer()->sourceFileExists($path)) {
            throw new NotFoundHttpException;
        }

        if ($this->getServer()->cacheFileExists($path, []) && $this->getServer()->getSource()->getTimestamp($path) >= $this->getServer()->getCache()->getTimestamp($path)) {
            $this->getServer()->deleteCache($path);
        }

        if ($this->getComponent()->signKey) {
            $request = Request::create(Yii::$app->request->getUrl());
            if (!$this->validateRequest($request)) {
                throw new BadRequestHttpException('Wrong signature');
            };
        }

        try {
            Yii::$app->getResponse()->format = Response::FORMAT_RAW;
            $this->getServer()->outputImage($path, Yii::$app->request->get());
        } catch (\Exception $e) {
            throw new NotSupportedException($e->getMessage());
        }
    }

    /**
     * @return \League\Glide\Server
     * @throws \yii\base\InvalidConfigException
     */
    protected function getServer()
    {
        return $this->getComponent()->getServer();
    }

    /**
     * @return \trntv\glide\components\Glide;
     * @throws \yii\base\InvalidConfigException
     */
    public function getComponent()
    {
        return Yii::$app->get($this->component);
    }

    /**
     * @param Request $request
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function validateRequest(Request $request)
    {
        return $this->getComponent()->validateRequest($request);
    }
}
