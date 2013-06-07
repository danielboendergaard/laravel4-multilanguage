<?php namespace Danielboendergaard\Laravel4Multilanguage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

class UrlGenerator extends BaseUrlGenerator {

    /**
     * The current locale
     * @var null|string
     */
    protected $locale;

    /**
     * Create a new URL Generator instance.
     *
     * @param  \Symfony\Component\Routing\RouteCollection $routes
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @param  string $locale
     * @return \Danielboendergaard\Laravel4Multilanguage\UrlGenerator
     */
    public function __construct(RouteCollection $routes, Request $request, $locale = null)
    {
        parent::__construct($routes, $request);

        $this->locale = $locale;
    }

    /**
     * Generate a absolute URL to the given path.
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @param  bool    $secure
     * @return string
     */
    public function to($path, $parameters = array(), $secure = null)
    {
        if ($this->isValidUrl($path)) return $path;

        $scheme = $this->getScheme($secure);

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $tail = implode('/', (array) $parameters);

        $root = $this->getRootUrl($scheme);

        // Prefix locale
        if ($this->locale and ($this->request->getPathInfo() != $path))
        {
            $root = rtrim($root, '/').'/'.$this->locale;
        }

        return trim($root.'/'.trim($path.'/'.$tail, '/'), '/');
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool    $absolute
     * @return string
     */
    public function route($name, $parameters = array(), $absolute = true)
    {
        $route = $this->routes->get($name);

        // Prefix locale
        if ($this->locale)
        {
            $route->setPath($this->locale.$route->getPath());
        }

        $parameters = (array) $parameters;

        if (isset($route) and $this->usingQuickParameters($parameters))
        {
            $parameters = $this->buildParameterList($route, $parameters);
        }

        return $this->generator->generate($name, $parameters, $absolute);
    }
}