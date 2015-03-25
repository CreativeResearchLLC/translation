<?php

namespace Stevebauman\Translation\Tests;

use Mockery as m;
use Stevebauman\Translation\Models\Locale as LocaleModel;
use Stevebauman\Translation\Models\LocaleTranslation as TranslationModel;
use Stevebauman\Translation\Translation;

class TranslationTest extends FunctionalTestCase
{
    protected $translation;

    protected $mockedApp;

    protected $mockedConfig;

    protected $mockedSession;

    protected $mockedCache;

    public function setUp()
    {
        parent::setUp();

        $this->setMocks();

        $this->mockedConfig->shouldReceive('get')->andReturnValues(array(
            'en',
            30,
            array(
                'en' => 'English',
                'fr' => 'French',
            ),
        ));

        $this->translation = new Translation(
            $this->mockedApp,
            $this->mockedConfig,
            $this->mockedSession,
            $this->mockedCache,
            new LocaleModel,
            new TranslationModel
        );
    }

    private function setMocks()
    {
        $this->mockedApp = m::mock('Illuminate\Foundation\Application');
        $this->mockedConfig = m::mock('Illuminate\Config\Repository');
        $this->mockedSession = m::mock('Illuminate\Session\SessionManager');
        $this->mockedCache = m::mock('Illuminate\Cache\CacheManager');
    }

    private function prepareMockedCacheForTranslate()
    {
        $this->mockedCache
            ->shouldReceive('get')->once()->andReturn(false)
            ->shouldReceive('has')->once()->andReturn(false)
            ->shouldReceive('put')->once()->andReturn(false);
    }


    private function prepareMockedSessionForTranslate($locale = 'en')
    {
        $this->mockedSession
            ->shouldReceive('get')->once()->andReturn(false)
            ->shouldReceive('set')->once()->andReturn(true)
            ->shouldReceive('get')->once()->andReturn($locale);
    }

    private function prepareMockedAppForTranslate()
    {
        $this->mockedApp->shouldReceive('setLocale')->once()->andReturn(true);
    }

    public function testDefaultTranslate()
    {
        $this->prepareMockedCacheForTranslate();

        $this->prepareMockedSessionForTranslate();

        $this->prepareMockedAppForTranslate();

        $result = $this->translation->translate('test');

        $this->assertEquals('test', $result);
        $this->assertEquals('en', $this->translation->getDefaultLocale());

        $locale = LocaleModel::first();

        $this->assertEquals('en', $locale->code);

        $translation = TranslationModel::first();

        $this->assertEquals(1, $translation->locale_id);
        $this->assertEquals('test', $translation->translation);
    }

    public function testSetLocale()
    {
        $this->prepareMockedCacheForTranslate();

        $this->prepareMockedSessionForTranslate('fr');

        $this->prepareMockedAppForTranslate();

        $this->translation->setLocale('fr');

        $this->assertEquals('fr', $this->translation->getLocale());
    }

    public function testTranslateToFrench()
    {
        $this->prepareMockedCacheForTranslate();

        $this->prepareMockedSessionForTranslate('fr');

        $this->prepareMockedAppForTranslate();

        $result = $this->translation->translate('Home');

        $this->assertEquals('Maison', $result);

        $locales = LocaleModel::get();

        $this->assertEquals('fr', $locales->get(1)->code);
        $this->assertEquals('French', $locales->get(1)->name);

        $translations = TranslationModel::get();

        $english = $translations->get(0);
        $this->assertEquals(1, $english->locale_id);
        $this->assertEquals('Home', $english->translation);

        $french = $translations->get(1);
        $this->assertEquals(2, $french->locale_id);
        $this->assertEquals(1, $french->translation_id);
        $this->assertEquals('Maison', $french->translation);
    }
}