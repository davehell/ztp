<?php

use Nette\Application\UI;

/**
 * Komponenta na přiřazení testera ke změně
 */
class TesteriControl extends UI\Control
{

	/** @var array of function ($tester, $zmena) */
	public $onPrirazeni;

	public function __construct()
	{
		parent::__construct();
	}


	public function handlePrirazeni($tester, $zmena)
	{
		$this->onPrirazeni($tester, $zmena);
	}

	public function render($testeri, $zmena)
	{
		$template = $this->template;
		$template->testeri = $testeri;
		$template->zmena = $zmena;
		$template->render(__DIR__ . '/TesteriControl.latte');
	}
}
