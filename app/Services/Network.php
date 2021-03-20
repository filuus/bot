<?php

namespace App\services;

use Phpml\Classification\MLPClassifier;
use Phpml\Exception\InvalidArgumentException;
use Phpml\NeuralNetwork\ActivationFunction\PReLU;
use Phpml\NeuralNetwork\ActivationFunction\Sigmoid;
use Phpml\NeuralNetwork\Layer;
use Phpml\NeuralNetwork\Node\Neuron;

class Network
{
    public $counter;
    public $mlp;

    public function __construct()
    {
        $this->counter = 0;
        $this->mlp = new MLPClassifier(60, [30], [-1, 0, 1]);
    }

    public function increment()
    {
        $this->counter++;
    }
}