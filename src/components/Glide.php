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
use League\Glide\Http\UrlBuilderFactory;
use League\Glide\Server;
use Symfony\Component\HttpFoundation\Request;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * @author Eugene Terentev <eugene@terentev.net>
 */
class Glide extends Component
{
    public $sourcePath;
    public $cachePath;
    public $signKey;

    protected $baseUrl;
    protected $source;
    protected $cache;
    protected $server;

    private $urlBuilder;

    /**
     * @return Server
     */
    public function getServer()
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


        $api = new Api($imageManager, $manipulators);
        $server = new Server($this->getSource(), $this->getCache(), $api);
        if ($this->baseUrl !== null) {
            $server->setBaseUrl($this->baseUrl);
        }
        return $server;
    }

    public function getSource()
    {
        if (!$this->source && $this->sourcePath) {
           $this->source = new Filesystem(
               new Local(Yii::getAlias($this->sourcePath))
           );
        }
        return $this->source;
    }

    public function getCache()
    {
        if (!$this->cache && $this->cachePath) {
            $this->cache = new Filesystem(
                new Local(Yii::getAlias($this->cachePath))
            );
        }
        return $this->cache;
    }

    public function setSource(FilesystemInterface $source)
    {
        $this->source = $source;
    }

    public function setCache(FilesystemInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = Yii::getAlias($baseUrl);
    }

    public function getUrlBuilder()
    {
        if (!$this->urlBuilder) {
            if (!$this->baseUrl) {
                throw new InvalidConfigException();
            }
            $this->urlBuilder = UrlBuilderFactory::create($this->baseUrl, $this->signKey);
        }
        return $this->urlBuilder;
    }

    public function getImageUrl($path, $options)
    {
        return $this->getUrlBuilder()->getUrl($path, $options);
    }

    public function validateRequest(Request $request)
    {
        try {
            SignatureFactory::create($this->signKey)->validateRequest($request);
        } catch (SignatureException $e) {
            return false;
        }
        return true;
    }
}
