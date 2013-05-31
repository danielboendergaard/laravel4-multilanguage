<?php namespace Danielboendergaard\Laravel4Multilanguage;

use Config;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class Laravel4MultilanguageServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('danielboendergaard/laravel4-multilanguage');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $languages = Config::get('app.languages');

        $locale = $this->app['request']->segment(1);

        if (in_array($locale, $languages))
        {
            Config::set('app.locale', $locale);

            $this->removeLocaleFromRequest($locale);
        }
        else
        {
            $locale = null;
        }

        $this->registerUrlGenerator($locale);
	}

    /**
     * Replace the current request object with a modified one where the locale is removed
     *
     * @param $locale
     */
    protected function removeLocaleFromRequest($locale)
    {
        $request = Request::createFromGlobals();

        $uri = substr($this->app['request']->server->get('REQUEST_URI'), strlen($locale) + 1);

        $request->server->set('REQUEST_URI', $uri);

        $this->app['request'] = $request;
    }

    /**
     * Register the URL generator service.
     *
     * @param $locale
     * @return void
     */
    protected function registerUrlGenerator($locale)
    {
        $this->app['url'] = $this->app->share(function($app) use ($locale)
        {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            return new UrlGenerator($routes, $app['request'], $locale);
        });
    }
}