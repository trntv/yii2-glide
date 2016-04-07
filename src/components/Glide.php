<?php
namespace trntv\glide\components;

use League\Glide\Manipulators\Background;
use League\Glide\Manipulators\Border;
use League\Glide\Manipulators\Encode;
use League\Glide\Manipulators\Watermark;
use Yii;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Glide\Api\Api;
use League\Glide\Manipulators\Blur;
use League\Glide\Manipulators\Brightness;
use League\Glide\Manipulators\Contrast;
use League\Glide\Manipulators\Filter;
use League\Glide\Manipulators\Gamma;
use League\Glide\Manipulators\Orientation;
use League\Glide\Manipulators\Pixelate;
use League\Glide\Manipulators\Sharpen;
use League\Glide\Manipulators\Size;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;
use League\Glide\Server;
use League\Uri\Components\Query;
use League\Uri\Schemes\Http;
use Symfony\Component\HttpFoundation\Request;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 * @param $source \League\Flysystem\FilesystemInterface
 * @param $cache \League\Flysystem\FilesystemInterface
 * @param $server \League\Glide\Server
 * @param $httpSignature \League\Glide\Signatures\Signature
 * @param $urlBuilder \League\Glide\Urls\UrlBuilderFactory
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
     * @var string|bool|null
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
     * @inheritdoc
     */
    public function init()
    {
        if (YII_ENV_PROD && !$this->signKey && !$this->maxImageSize) {
            Yii::warning(
                'It is highly recommended to use secure url or set "maxImageSize" on production environments',
                'glide'
            );
        }
        parent::init();
    }

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
            new Brightness(),
            new Contrast(),
            new Gamma(),
            new Sharpen(),
            new Filter(),
            new Blur(),
            new Pixelate(),
            new Background(),
            new Border(),
            new Encode(),
            new Sharpen(),
            new Watermark()
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
     * @return \League\Glide\Signatures\Signature
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
     */
    public function outputImage($path, $params = [])
    {
        $this->getServer()->outputImage($path, $params);
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
            $uri = Http::createFromString($resultUrl);
            $path = $uri->getPath();
            $urlParams = $uri->query->toArray();
        } else {
            $path = '/index.php';
            $route = array_shift($params);
            $urlParams = $params;
            $urlParams['r'] = $route;
        }

        if ($this->signKey) {
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
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function signUrl($url, array $params = [])
    {
        $uri = Http::createFromString($url);
        $paramsQuery = Query::createFromArray($params);
        $path = $uri->getPath();
        $query = $uri->query->merge($paramsQuery);
        $signature = $this->getHttpSignature()->generateSignature($path, $query->toArray());
        $query = $query->merge(Query::createFromArray(['s' => $signature]));
        $uri = $uri->withQuery((string) $query);
        return (string) $uri;
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
                $httpSignature->validateRequest($request->getPathInfo(), $request->query->all());
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
