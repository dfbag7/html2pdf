<?php namespace Dimbo\Html2Pdf;

use Illuminate\Support\ServiceProvider;

class Html2PdfServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->package('dimbo/html2pdf', 'html2pdf');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('html2pdf', function()
        {
            return new Html2Pdf($this->app['config']->get('html2pdf::pathToBinary'),
                $this->app['Psr\Log\LoggerInterface']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['html2pdf'];
    }

}
