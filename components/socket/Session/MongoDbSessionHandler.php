<?php

namespace app\components\socket\Session;

use Symfony\Component\HttpFoundation\Session\SessionUtils;
use Yii;
use yii\di\Instance;
use yii\mongodb\Connection;

/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/3
 * Time: 17:50
 */
class MongoDbSessionHandler implements \SessionHandlerInterface
{
    private $sessionName;
    private $prefetchId;
    private $prefetchData;
    private $newSessionId;
    private $igbinaryEmptyData;

    /**
     * @var Connection|array|string the MongoDB connection object or the application component ID of the MongoDB connection.
     * After the Session object is created, if you want to change this property, you should only assign it
     * with a MongoDB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'mongodb';
    public $sessionCollection = 'session';


    /**
     * @var callable a callback that will be called during session data reading.
     * The signature of the callback should be as follows:
     *
     * ```
     * function ($fields)
     * ```
     *
     * where `$fields` is the storage field set for read session and `$session` is this session instance.
     * If callback returns an array, it will be merged into the session data.
     *
     * For example:
     *
     * ```php
     * function ($fields) {
     *     return [
     *         'expireDate' => Yii::$app->formatter->asDate($fields['expire']),
     *     ];
     * }
     * ```
     */
    public $readCallback;


    private $mongo;

    /**
     * @var \MongoDB\Collection
     */
    private $collection;

    /**
     * @var array
     */
    private $options;

//    public function __construct(\MongoDB\Client $mongo, array $options)
//    {
//        if (!isset($options['database']) || !isset($options['collection'])) {
//            throw new \InvalidArgumentException('You must provide the "database" and "collection" option for MongoDBSessionHandler');
//        }
//
//        $this->mongo = $mongo;
//
//        $this->options = array_merge([
//            'id_field' => '_id',
//            'data_field' => 'data',
//            'time_field' => 'time',
//            'expiry_field' => 'expires_at',
//        ], $options);
//    }

    public function __construct()
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }

//        register_shutdown_function([$this, 'close']);
//        if ($this->getIsActive()) {
//            Yii::warning('Session is already started', __METHOD__);
//            $this->updateFlashCounters();
//        }
        $this->db = Instance::ensure($this->db, Connection::class);
    }


    public function open($savePath, $sessionName)
    {
        $this->sessionName = $sessionName;
        if (!headers_sent() && !ini_get('session.cache_limiter') && '0' !== ini_get('session.cache_limiter')) {
            header(sprintf('Cache-Control: max-age=%d, private, must-revalidate', 60 * (int)ini_get('session.cache_expire')));
        }

        return true;
    }

    public function validateId($sessionId)
    {
        $this->prefetchData = $this->read($sessionId);
        $this->prefetchId = $sessionId;

        return '' !== $this->prefetchData;
    }

    /**
     * @return string
     */
    public function read($sessionId)
    {
        if (null !== $this->prefetchId) {
            $prefetchId = $this->prefetchId;
            $prefetchData = $this->prefetchData;
            $this->prefetchId = $this->prefetchData = null;

            if ($prefetchId === $sessionId || '' === $prefetchData) {
                $this->newSessionId = '' === $prefetchData ? $sessionId : null;

                return $prefetchData;
            }
        }

        $data = $this->doRead($sessionId);
        $this->newSessionId = '' === $data ? $sessionId : null;

        return $data;
    }

    public function write($sessionId, $data)
    {
        if (null === $this->igbinaryEmptyData) {
            // see https://github.com/igbinary/igbinary/issues/146
            $this->igbinaryEmptyData = \function_exists('igbinary_serialize') ? igbinary_serialize([]) : '';
        }
        if ('' === $data || $this->igbinaryEmptyData === $data) {
            return $this->destroy($sessionId);
        }
        $this->newSessionId = null;

        return $this->doWrite($sessionId, $data);
    }

    public function destroy($sessionId)
    {
        if (!headers_sent() && filter_var(ini_get('session.use_cookies'), FILTER_VALIDATE_BOOLEAN)) {
            if (!$this->sessionName) {
                throw new \LogicException(sprintf('Session name cannot be empty, did you forget to call "parent::open()" in "%s"?.', \get_class($this)));
            }
            $cookie = SessionUtils::popSessionCookie($this->sessionName, $sessionId);

            /*
             * We send an invalidation Set-Cookie header (zero lifetime)
             * when either the session was started or a cookie with
             * the session name was sent by the client (in which case
             * we know it's invalid as a valid session cookie would've
             * started the session).
             */
            if (null === $cookie || isset($_COOKIE[$this->sessionName])) {
                if (\PHP_VERSION_ID < 70300) {
                    setcookie($this->sessionName, '', 0, ini_get('session.cookie_path'), ini_get('session.cookie_domain'), filter_var(ini_get('session.cookie_secure'), FILTER_VALIDATE_BOOLEAN), filter_var(ini_get('session.cookie_httponly'), FILTER_VALIDATE_BOOLEAN));
                } else {
                    $params = session_get_cookie_params();
                    unset($params['lifetime']);
                    setcookie($this->sessionName, '', $params);
                }
            }
        }

        return $this->newSessionId === $sessionId || $this->doDestroy($sessionId);
    }


    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }


    /**
     * Composes storage field set for session writing.
     * @param string $id Optional session id
     * @param string $data Optional session data
     * @return array storage fields
     */
    protected function composeFields($id = null, $data = null)
    {
        $fields = $this->writeCallback ? call_user_func($this->writeCallback, $this) : [];
        if ($id !== null) {
            $fields['id'] = $id;
        }
        if ($data !== null) {
            $fields['data'] = $data;
        }
        return $fields;
    }

    /**
     * Extracts session data from storage field set.
     * @param array $fields storage fields.
     * @return string session data.
     */
    protected function extractData($fields)
    {
        if ($this->readCallback !== null) {
            if (!isset($fields['data'])) {
                $fields['data'] = '';
            }
            $extraData = call_user_func($this->readCallback, $fields);
            if (!empty($extraData)) {
                session_decode($fields['data']);
                $_SESSION = array_merge((array)$_SESSION, (array)$extraData);
                return session_encode();
            }

            return $fields['data'];
        }

        return isset($fields['data']) ? $fields['data'] : '';
    }


    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId)
    {
        $this->db->getCollection($this->sessionCollection)->remove(
            ['id' => $sessionId],
            ['justOne' => true]
        );

        return true;
    }

    public function gc($maxlifetime)
    {
        $this->db->getCollection($this->sessionCollection)
            ->remove(['expire' => ['$lt' => time()]]);

        return true;
    }


    public $writeCallback;
    protected $fields = [];

    /**
     * @return int the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
     */
    public function getTimeout()
    {
        return (int)ini_get('session.gc_maxlifetime');
    }

    /**
     * @param int $value the number of seconds after which data will be seen as 'garbage' and cleaned up
     */
    public function setTimeout($value)
    {
//        $this->freeze();
        ini_set('session.gc_maxlifetime', $value);
//        $this->unfreeze();
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data)
    {
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        try {
            if ($this->writeCallback && !$this->fields) {
                $this->fields = $this->composeFields();
            }

            // ensure data consistency
            if (!isset($this->fields['data'])) {
                $this->fields['data'] = $data;
            } else {
            }

            // ensure 'id' and 'expire' are never affected by [[writeCallback]]
            $this->fields = array_merge($this->fields, [
                'id' => $sessionId,
                'expire' => time() + $this->getTimeout(),
            ]);

            $this->db->getCollection($this->sessionCollection)->update(
                ['id' => $sessionId],
                $this->fields,
                ['upsert' => true]
            );

            $this->fields = [];

        } catch (\Exception $e) {
            Yii::$app->errorHandler->handleException($e);
            return false;
        }

        return true;
    }

    public function updateTimestamp($sessionId, $data)
    {
        $expiry = time() + $this->getTimeout();
        $this->db->getCollection($this->sessionCollection)->update(
            ['id' => $sessionId],
            [
                'expire' => $expiry,
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId)
    {
        $collection = $this->db->getCollection($this->sessionCollection);
        $condition = [
            'id' => $sessionId,
            'expire' => ['$gt' => time()],
        ];

        if (isset($this->readCallback)) {
            $doc = $collection->findOne($condition);
            return $doc === null ? '' : $this->extractData($doc);
        }

        $doc = $collection->findOne(
            $condition,
            ['data' => 1, '_id' => 0]
        );
        return isset($doc['data']) ? $doc['data'] : '';
    }

}