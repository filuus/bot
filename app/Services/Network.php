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
    protected $mlp;

    public function __construct()
    {
        $this->mlp = new MLPClassifier(60, [30], [-1, 0, 1]);
    }
}