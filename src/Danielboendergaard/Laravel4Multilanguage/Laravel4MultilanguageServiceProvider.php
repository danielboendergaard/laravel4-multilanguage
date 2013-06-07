<?php namespace Danielboendergaard\Laravel4Multilanguage;

use Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
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
        $languages = Config::get('app.languages', []);

        $locale = $this->app['request']->segment(1);

        if (in_array($locale, $languages))
        {
            Config::set('app.locale', $locale);
        }
        else
        {
            $locale = null;
        }

        $this->registerRouter($locale);

        $this->registerUrlGenerator($locale);

        // Reregister redirector to use custom UrlGenerator
        $this->registerRedirector();
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
     * Register the router instance.
     *
     * @param $locale
     * @return void
     */
    protected function registerRouter($locale)
    {
        $this->app['router'] = $this->app->share(function($app) use ($locale)
        {
            $router = new Router($app, $locale);

            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing')
            {
                $router->disableFilters();
            }

            return $router;
        });
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

    /**
     * Register the Redirector service.
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app['redirect'] = $this->app->share(function($app)
        {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session']))
            {
                $redirector->setSession($app['session']);
            }

            return $redirector;
        });
    }
}