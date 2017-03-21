<?php
namespace lib\framework\main;

use lib\framework\exception\SystemException;

/**
 *系统配置缓处理类 配置文件中的常亮将直接被解析 无需使用 constant()取值
 *
 * Date: 17-3-15
 * Time: 下午11:10
 * author :李华 yehong0000@163.com
 */
class Config {
    //配置缓存,简单处理的一维数组
    private $config;
    //经过处理的二维数组
    private $dimensionalConfig;
    //配置文件
    private $configFile;

    //环境设置
    private $environs;
    //基础前缀键，设置之后点语法取配置可省略相应部分
    private $baseKey;
    protected static $objArr;

    private function __construct($fileName) {
        $this->configFile = $fileName . '.ini';
    }

    /**
     * 获取实例
     *
     * @param string $fileName
     *
     * @return Config
     */
    public static function getInstance($fileName = 'main') {
        if (!isset(self::$objArr[$fileName])) {
            self::$objArr[$fileName] = new self($fileName);
        }
        return self::$objArr[$fileName];
    }

    /**
     * 获取配置文件内容
     *
     * @return object
     * @throws systemException
     */
    private function getConfig() {
        if (!isset($this->dimensionalConfig)) {
            $file = CONF_PATH . $this->configFile;
            if (!is_file($file)) {
                throw new systemException('找不到配置文件' . $this->configFile, 8010);
            }
            if (!is_writable($file)) {
                throw new systemException('无法读取相应配置文件' . $this->configFile . '，请检查', 8011);
            }
            $this->config = parse_ini_file($file, true);
            if ($this->config === false) {
                $this->config = [];
                throw new systemException('配置文件' . $this->configFile . '解析失败', 8012);
            }
            $keys = array_keys($this->config);
            $config = [];
            $commonConfig = [];
            foreach ($keys as $v) {
                if (is_array($this->config[$v])) {
                    //处理配置节继承
                    if (strpos($v, ':') !== false) {
                        $mereKeys = explode(':', $v);
                        $k = trim(reset($mereKeys));
                        krsort($mereKeys);
                        $config[$k] = $this->config[$v];
                        foreach ($mereKeys as $sK) {
                            $sK = trim($sK);
                            $config[$k] = array_merge($config[$k], isset($this->config[$sK]) && is_array($this->config[$sK]) ? $this->config[$sK] : []);
                        }
                    } else {
                        $config[$v] = $this->config[$v];
                    }
                } else {
                    $commonConfig[$v] = $this->config[$v];
                }
            }
            $environ = $this->getEnviron('ENVIRON');
            if ($environ && is_string($environ) && isset($config[$environ]) && is_array($config[$environ])) {
                $commonConfig = array_merge($commonConfig, $config[$environ]);
            }
            $this->config = $commonConfig;
            $config = [];
            foreach ($commonConfig as $cK => $cV) {
                if (strpos($cK, '.') !== false) {
                    $cKeys = explode('.', $cK);
                    $config = array_merge_recursive($config, $this->toArray($cKeys, $cV));
                } else {
                    $config[$cK] = $cV;
                }
            }
            $this->dimensionalConfig = $config;
        }
        return $this->dimensionalConfig;
    }

    /**
     * 递归组合二维数组
     *
     * @param $keys
     * @param $val
     * @return array
     */
    private function toArray($keys, $val) {
        $k = array_shift($keys);
        $v = [];
        if ($keys) {
            $v[$k] = $this->toArray($keys, $val);
        } else {
            $v[$k] = $val;
        }
        return $v;
    }

    /**
     * 获取环境配置
     *
     * @return array
     * @throws systemException
     */
    private function getEnvirons() {
        $file = ROOT . DS . '.env';
        if (!is_file($file)) {
            throw new systemException('找不到环境配置文件.env', 8020);
        }
        if (!is_writable($file)) {
            throw new systemException('无法读取配置文件.env', 8021);
        }
        $this->environs = parse_ini_file($file, true);
        if ($this->environs === false) {
            $this->environs = [];
            throw new systemException('环境配置文件.env解析失败', 8022);
        }
        return $this->environs;
    }

    /**
     * 获取一个环境配置
     *
     * @param string $sk
     * @return null|string
     */
    public function getEnviron($sk = '') {
        if ($sk && is_string($sk)) {
            return $this->getEnvirons()[$sk] ?: '';
        }
        return null;
    }


    /**
     * 递归取得二维数组内容
     *
     * @param $keys
     * @param array $val
     * @return array|null
     */
    private function recursionGetValue($keys, $val = []) {
        $key = array_shift($keys);
        $val = $val ?: (isset($this->dimensionalConfig[$key]) ? $this->dimensionalConfig[$key] : null);
        if (!$val) {
            return null;
        }
        if ($keys) {
            return $this->recursionGetValue($keys, $val);
        } else {
            return isset($val[$key]) ? $val[$key] : null;
        }
    }

    /**
     * 获取所有配置
     *
     * @return object
     * @throws systemException
     */
    public function getConfigs() {
        return (object)$this->getConfig();
    }

    /**
     * 获取指定配置，支持点语法
     *
     * @param $field
     * @param bool $fullPath 忽略baseKey全路径查询
     * @return array|null
     */
    public function get($field, $fullPath = false) {
        if ($field && is_string($field)) {
            if ($fullPath !== true && isset($this->baseKey) && is_string($this->baseKey)) {
                $field = $this->baseKey . '.' . $field;
            }
            $this->getConfig();
            if (isset($this->config[$field])) {
                return $this->config[$field];
            } else {
                if (strpos($field, '.')) {
                    return $this->recursionGetValue(explode('.', $field));
                } else {
                    return isset($this->dimensionalConfig[$field]) ? $this->dimensionalConfig[$field] : null;
                }
            }
        }
        return null;
    }

    /**
     * 设置基础前缀键
     * @param $key
     */
    public function setBaseKey($key) {
        $this->baseKey = $key;
    }
}