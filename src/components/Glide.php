<?php
namespace trntv\glide\components;

use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Glide\Api\Api;
use League\Glide\Api\Manipulator\Blur;
use League\Glide\Api\Manipulator\Brightness;
use League\Glide\Api\Manipulator\Contrast;
use League\Glide\Api\Manipulator\Filter;
use League\Glide\Api\Manipulator\Gamma;
use League\Glide\Api\Manipulator\Orientation;
use League\Glide\Api\Manipulator\Output;
use League\Glide\Api\Manipulator\Pixelate;
use League\Glide\Api\Manipulator\Rectangle;
use League\Glide\Api\Manipulator\Sharpen;
use League\Glide\Api\Manipulator\Size;
use League\Glide\Http\SignatureException;
use League\Glide\Http\SignatureFactory;
use League\Glide\Http\UrlBuilder;
use League\Glide\Http\UrlBuilderFactory;
use League\Glide\Server;
use League\Url\Url;
use Symfony\Component\HttpFoundation\Request;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 * @param $source \League\Flysystem\FilesystemInterface
 * @param $cache \League\Flysystem\FilesystemInterface
 * @param $server \League\Glide\Server
 * @param $httpSignature \League\Glide\Http\Signature
 * @param $urlBuilder \League\Glide\Http\UrlBuilderFactory
 */
class Glide extends Component
{
    /**
     * @var string
     */
    public $sourcePath;
    /**
     * @var string
     */
    public $sourcePathPrefix;
    /**
     * @var string
     */
    public $cachePath;
    /**
     * @var string
     */
    public $cachePathPrefix;
    /**
     * Sign key. false if you do not want to use HTTP signatures
     * @var string|bool
     */
    public $signKey;
    /**
     * @var string
     */
    public $maxImageSize;
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var string
     */
    public $urlManager = 'urlManager';

    /**
     * @var
     */
    protected $source;
    /**
     * @var
     */
    protected $cache;
    /**
     * @var
     */
    protected $server;
    /**
     * @var
     */
    protected $httpSignature;
    /**
     * @var
     */
    protected $urlBuilder;

    /**
     * @param FilesystemInterface $source
     */
    public function setSource(FilesystemInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @return Filesystem
     */
    public function getSource()
    {
        if (!$this->source && $this->sourcePath) {
            $this->source = new Filesystem(
                new Local(Yii::getAlias($this->sourcePath))
            );
        }
        return $this->source;
    }

    /**
     * @param FilesystemInterface $cache
     */
    public function setCache(FilesystemInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return Filesystem
     */
    public function getCache()
    {
        if (!$this->cache && $this->cachePath) {
            $this->cache = new Filesystem(
                new Local(Yii::getAlias($this->cachePath))
            );
        }
        return $this->cache;
    }

    /**
     * @return Api
     */
    public function getApi()
    {
        $imageManager = new ImageManager([
            'driver' => extension_loaded('imagick') ? 'imagick' : 'gd'
        ]);
        $manipulators = [
            new Size($this->maxImageSize),
            new Orientation(),
            new Rectangle(),
            new Brightness(),
            new Contrast(),
            new Gamma(),
            new Sharpen(),
            new Filter(),
            new Blur(),
            new Pixelate(),
            new Output()
        ];

        return new Api($imageManager, $manipulators);
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        $server = new Server($this->getSource(), $this->getCache(), $this->getApi());
        if ($this->baseUrl !== null) {
            $server->setBaseUrl($this->baseUrl);
        }
        if ($this->sourcePathPrefix !== null) {
            $server->setSourcePathPrefix($this->sourcePathPrefix);
        }
        if ($this->cachePathPrefix !== null) {
            $server->setCachePathPrefix($this->cachePathPrefix);
        }
        return $server;
    }

    /**
     * @param UrlBuilder $urlBuider
     */
    public function setUrlBuilder(UrlBuilder $urlBuider)
    {
        $this->urlBuilder = $urlBuider;
    }

    /**
     * @return UrlBuilder
     */
    public function getUrlBuilder()
    {
        if (!$this->urlBuilder) {
            $this->urlBuilder = UrlBuilderFactory::create($this->baseUrl, $this->signKey);
        }
        return $this->urlBuilder;
    }

    /**
     * @return \League\Glide\Http\Signature
     * @throws InvalidConfigException
     */
    public function getHttpSignature()
    {
        if ($this->httpSignature === null) {
            if ($this->signKey === null) {
                throw new InvalidConfigException;
            }
            $this->httpSignature = SignatureFactory::create($this->signKey);
        }
        return $this->httpSignature;

    }

    /**
     * @param $path
     * @param array $params
     * @return Request
     */
    public function outputImage($path, $params = [])
    {
        return $this->getServer()->outputImage($path, $params);
    }

    /**
     * @param array $params
     * @param bool $scheme
     * @return bool|string
     * @throws InvalidConfigException
     */
    public function createSignedUrl(array $params, $scheme = false)
    {
        $route = ArrayHelper::getValue($params, 0);
        if ($this->getUrlManager()->enablePrettyUrl) {
            $showScriptName = $this->getUrlManager()->showScriptName;
            if ($showScriptName) {
                $this->getUrlManager()->showScriptName = false;
            }
            $resultUrl = $this->getUrlManager()->createAbsoluteUrl($params);
            $this->getUrlManager()->showScriptName = $showScriptName;
            $path = parse_url($resultUrl, PHP_URL_PATH);
            parse_str(parse_url($resultUrl, PHP_URL_QUERY), $urlParams);
        } else {
            $path = '/index.php';
            unset($params[0]);
            $urlParams = $params;
        }

        if ($this->signKey != false) {
            $signature = $this->getHttpSignature()->generateSignature($path, $urlParams);
            $params['s'] = $signature;
        }

        $params[0] = $route;
        return $scheme
            ? $this->getUrlManager()->createAbsoluteUrl($params, $scheme)
            : $this->getUrlManager()->createUrl($params);
    }

    /**
     * @param $url
     * @param array $params
     * @return string
     * @throws InvalidConfigException
     */
    public function signUrl($url, array $params = [])
    {
        $url = Url::createFromUrl($url);
        $path = $url->getPath()->getUriComponent();
        $query = array_merge($url->getQuery()->toArray(), $params);
        $signature = $this->getHttpSignature()->generateSignature($path, $query);
        $query = array_merge($query, ['s' => $signature]);
        $url->setQuery($query);
        return (string) $url;
    }

    /**
     * @param $path
     * @param $params
     */
    public function signPath($path, array $params = [])
    {
        $this->getUrlBuilder()->getUrl($path, $params);
    }

    /**
     * @param Request $request
     * @return bool
     * @throws InvalidConfigException
     */
    public function validateRequest(Request $request)
    {
        if ($this->signKey !== null) {
            $httpSignature = $this->getHttpSignature();
            try {
                $httpSignature->validateRequest($request);
            } catch (SignatureException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return null|\yii\web\UrlManager
     * @throws InvalidConfigException
     */
    public function getUrlManager()
    {
        return Yii::$app->get($this->urlManager);
    }
}
