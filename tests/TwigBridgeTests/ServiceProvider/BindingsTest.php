<?php

namespace TwigBridgeTests\ServiceProvider;

use Mockery as m;
use Illuminate\View\Environment;
use TwigBridgeTests\Base;
use TwigBridge\TwigServiceProvider;

class BindingsTest extends Base
{
    public function testBindings()
    {
        $bindings = array(
            'twig.extensions',
            'twig.loader.path',
            'twig.loader.viewfinder',
            'twig.loader',
            'twig.options',
            'twig.bridge',
            'twig',
            'twig.engine',
        );

        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);

        // Make sure not found
        foreach ($bindings as $binding) {
            $this->assertFalse($app->bound($binding));
        }

        // Boot provider
        $provider->boot();

        // Now make sure bounded
        foreach ($bindings as $binding) {
            $this->assertTrue($app->bound($binding));
        }
    }

    public function testTwigOptions()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $config  = $app['config']->get('twigbridge::twig');
        $options = $app['twig.options'];

        // Make sure that twig.options sets the storage path automatically
        $this->assertEmpty($config['cache']);
        $this->assertEquals($options['cache'], realpath(__DIR__.'/..').'/storage/views/twig');


        // Make sure same config is returned
        $options['cache'] = null;

        $this->assertSame($options, $config);
    }

    public function testExtensions()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $this->assertSame($app['twig.extensions'], $app['config']->get('twigbridge::extensions.enabled'));
    }

    public function testExtensionsWithDebug()
    {
        $app = $this->getApplication(array(
            'twig' => array(
                'debug' => true
            )
        ));

        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $this->assertSame($app['twig.extensions'][0], 'Twig_Extension_Debug');
    }

    public function testLoaderPath()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $this->assertInstanceOf('TwigBridge\Twig\Loader\Path', $app['twig.loader.path']);
    }

    public function testLoaderViewfinder()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $app['twig.bridge'] = m::mock('stdClass');
        $app['twig.bridge']->shouldReceive('getExtension')->andReturn('twig');

        $this->assertInstanceOf('TwigBridge\Twig\Loader\Viewfinder', $app['twig.loader.viewfinder']);
    }

    public function testLoaderChain()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        // View
        $engine = m::mock('Illuminate\View\Engines\EngineResolver');
        $engine->shouldReceive('register');

        $finder = m::mock('Illuminate\View\ViewFinderInterface');
        $finder->shouldReceive('addExtension');
        $finder->shouldReceive('getPaths')->andReturn(array());
        $finder->shouldReceive('getHints')->andReturn(array());

        $app['view'] = new Environment(
            $engine,
            $finder,
            m::mock('Illuminate\Events\Dispatcher')
        );

        // TwigBridge
        $app['twig.bridge'] = m::mock('stdClass');
        $app['twig.bridge']->shouldReceive('getExtension')->andReturn('twig');

        // Loader
        $this->assertInstanceOf('Twig_Loader_Chain', $app['twig.loader']);
    }

    public function testTwigBridge()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $this->assertInstanceOf('TwigBridge\TwigBridge', $app['twig.bridge']);
    }

    public function testTwigExtension()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $this->assertEquals($app['twig.bridge']->getExtension(), 'twig');
    }

    public function testTwig()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        // Extensions
        $app['twig.extensions'] = array();

        // View
        $engine = m::mock('Illuminate\View\Engines\EngineResolver');
        $engine->shouldReceive('register');

        $finder = m::mock('Illuminate\View\ViewFinderInterface');
        $finder->shouldReceive('addExtension');
        $finder->shouldReceive('getPaths')->andReturn(array());
        $finder->shouldReceive('getHints')->andReturn(array());

        $app['view'] = new Environment(
            $engine,
            $finder,
            m::mock('Illuminate\Events\Dispatcher')
        );

        $this->assertInstanceOf('Twig_Environment', $app['twig']);
    }

    public function testTwigEngine()
    {
        $app      = $this->getApplication();
        $provider = new TwigServiceProvider($app);
        $provider->boot();

        // Extensions
        $app['twig.extensions'] = array();

        // View
        $engine = m::mock('Illuminate\View\Engines\EngineResolver');
        $engine->shouldReceive('register');

        $finder = m::mock('Illuminate\View\ViewFinderInterface');
        $finder->shouldReceive('addExtension');
        $finder->shouldReceive('getPaths')->andReturn(array());
        $finder->shouldReceive('getHints')->andReturn(array());

        $app['view'] = new Environment(
            $engine,
            $finder,
            m::mock('Illuminate\Events\Dispatcher')
        );

        $this->assertInstanceOf('TwigBridge\Engine\Twig', $app['twig.engine']);
    }

    public function testRegisteredEngine()
    {
        $app = $this->getApplication();

        // View
        $engine = new \Illuminate\View\Engines\EngineResolver;

        $finder = m::mock('Illuminate\View\ViewFinderInterface');
        $finder->shouldReceive('addExtension');
        $finder->shouldReceive('getPaths')->andReturn(array());
        $finder->shouldReceive('getHints')->andReturn(array());

        $app['view'] = new Environment(
            $engine,
            $finder,
            m::mock('Illuminate\Events\Dispatcher')
        );

        $provider = new TwigServiceProvider($app);
        $provider->boot();

        $app['twig.extensions'] = array();


        $this->assertInstanceOf('TwigBridge\Engine\Twig', $app['view']->getEngineResolver()->resolve('twig'));
    }
}
