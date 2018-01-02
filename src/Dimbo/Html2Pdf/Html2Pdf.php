<?php namespace Dimbo\Html2Pdf;

use Symfony\Component\Process\Process;

class Html2Pdf
{
    /** @var  string */
    protected $pathToBinary;

    /** @var  \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var array */
    protected $globalOptions = [];

    /** @var array|null */
    protected $tocOptions;

    /** @var array */
    protected $pages = [];

    /** @var array */
    protected $covers = [];

    /** @var  string */
    protected $outputFile;

    /**
     * Html2Pdf constructor.
     *
     * @param string $pathToBinary
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($pathToBinary, $logger)
    {
        if( !file_exists($pathToBinary) )
            throw new Html2PdfError(0, 'WKHTMLTOPDF executable not found');

        $this->pathToBinary = $pathToBinary;
        $this->logger = $logger;

        $this->setGlobalOption('--quiet');
    }

    private function appendOneOption(&$str, $option, $value = null)
    {
        if(strlen($str) >= 1 && substr($str, -1, 1) !== ' ')
        {
            $str .= ' ';
        }

        $str .= $option;

        if($value !== null)
            $str .= ' ' . escapeshellarg($value);
    }

    public function constructOptions($options)
    {
        $result = '';

        foreach($options as $name => $value)
        {
            if(is_integer($name))
            {
                $this->appendOneOption($result, $value);
            }
            elseif(is_array($value))
            {
                foreach((array)$value as $oneValue)
                {
                    $this->appendOneOption($result, $name, $oneValue);
                }
            }
            else
            {
                $this->appendOneOption($result, $name, $value);
            }
        }

        return $result;
    }

    protected function constructGlobalOptions()
    {
        return $this->constructOptions($this->globalOptions);
    }

    protected function constructCoverArgs()
    {
        $result = '';

        foreach($this->covers as $source => $options)
        {
            if($result !== '')
                $result .= ' ';

            $result .= 'cover ' . escapeshellarg($source);

            $optionsString = $this->constructOptions($options);
            if(!empty($optionsString))
                $result .= ' ' . $optionsString;
        }

        return $result;
    }

    protected function constructPageArgs()
    {
        $result = '';

        foreach($this->pages as $source => $options)
        {
            if($result !== '')
                $result .= ' ';

            $result .= 'page ' . escapeshellarg($source);

            $optionsString = $this->constructOptions($options);
            if(!empty($optionsString))
                $result .= ' ' . $optionsString;
        }

        return $result;
    }

    protected function constructTocArgs()
    {
        $result = '';

        if(is_array($this->tocOptions))
        {
            $result = $this->constructOptions($this->tocOptions);
        }

        return $result;
    }

    private static function appendArg(&$str, $arg)
    {
        if(!empty($arg))
        {
            if(strlen($str) >= 1 && substr($str, -1, 1) !== ' ')
            {
                $str .= ' ';
            }

            $str .= $arg;
        }
    }

    protected function constructArgs()
    {
        static::appendArg($result, $this->constructGlobalOptions());
        static::appendArg($result, $this->constructCoverArgs());
        static::appendArg($result, $this->constructPageArgs());
        static::appendArg($result, $this->constructTocArgs());
        static::appendArg($result, $this->outputFile);

        return $result;
    }

    /**
     * @param string $optName
     * @param string|null $value
     *
     * @return $this
     */
    public function setGlobalOption($optName, $value = null)
    {
        $this->globalOptions[$optName] = $value;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setGlobalOptions(array $options)
    {
        $this->globalOptions = array_merge_recursive($this->globalOptions, $options);

        return $this;
    }

    /**
     * @param string $source
     * @param array $options
     *
     * @return $this
     */
    public function addPage($source, array $options = [])
    {
        $this->pages[$source] = $options;

        return $this;
    }

    /**
     * @param string $viewName
     * @param array $data
     * @param array $options
     *
     * @return $this
     */
    public function addPageFromView($viewName, array $data = [], array $options = [])
    {
        $view = \View::make($viewName, $data);

        $fileName = getTempFile('htm', 'html');

        file_put_contents($fileName, $view->render());

        return $this->addPage($fileName, $options);
    }

    /**
     * @param string $source
     * @param array $options
     *
     * @return $this
     */
    public function addCover($source, array $options = [])
    {
        $this->covers[$source] = $options;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setTocOptions(array $options)
    {
        if(is_array($this->tocOptions))
            $this->tocOptions = array_merge_recursive($this->tocOptions, $options);
        else
            $this->tocOptions = $options;

        return $this;
    }

    public function setTocOption($optName, $value = null)
    {
        if(is_array($this->tocOptions))
            $this->tocOptions[$optName] = $value;
        else
            $this->tocOptions = [$optName => $value];
    }

    protected function exec($command)
    {
        if($this->logger)
            $this->logger->info('Command started: ' . $command);

        $process = new Process($command);
        $process->run();

        if($this->logger)
        {
            $this->logger->info('Command ended: ' . $command);

            $stdOut = $process->getOutput();
            if(!empty($stdOut))
                $this->logger->debug('STDOUT: ' . $process->getOutput());

            $stdErr = $process->getErrorOutput();
            if(!empty($stdErr))
                $this->logger->debug('STDERR: ' . $stdErr);

            $this->logger->debug('Return code: ' . $process->getExitCode());
        }

        return $process->getExitCode();
    }

    protected function checkReturnCode($returnCode)
    {
        if($returnCode !== 0)
        {
            throw new Html2PdfError($returnCode, 'Error ' . $returnCode);
        }
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function setOutput($fileName)
    {
        $this->outputFile = $fileName;

        return $this;
    }

    public function run()
    {
        $command = escapeshellarg($this->pathToBinary)
            . ' ' . $this->constructArgs();

        $resultCode = $this->exec($command);

        $this->checkReturnCode($resultCode);

        return $resultCode;
    }
}
