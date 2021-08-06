<?php namespace Dimbo\Html2Pdf;

use Illuminate\Support\ServiceProvider;

class Html2PdfServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/html2pdf.php', 'html2pdf');

        $this->app->bind('html2pdf', function()
        {
            return new Html2Pdf($this->app['config']->get('html2pdf.pathToBinary'),
                $this->app['Psr\Log\LoggerInterface'],
                $this->app->bound('stat') ? $this->app['stat'] : null);
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
