<?php
    namespace phpMl;

    use phpMl\classificacao\analise;
    use Phpml\Dataset\CsvDataset;
    use Phpml\FeatureExtraction\TokenCountVectorizer;
    use Phpml\Tokenization\WordTokenizer;
    use Phpml\FeatureExtraction\TfIdfTransformer;
    use Phpml\Dataset\ArrayDataset;
    use Phpml\CrossValidation\StratifiedRandomSplit;
    use Phpml\Metric\Accuracy;

    require __DIR__ . '/vendor/autoload.php';

    /* 1- carrega o dataset
        simplesmete carrega os textos dos tweets em forma de array,
        nada dmais
    */
    $dataset = new CsvDataset('datasets/clean_tweets_redz.csv', 1);

    $samples = [];
    foreach ($dataset->getSamples() as $value) {
        $samples[] = $value[0];
    }

    /* 2- prepara o dataset
        transfaorma cada tweet em um array, sendo cada palavra um valor
        depois, conta qunatas vezes determinada palavra aparece,
        pra calcular a relevancia dessa palavra
    */
    $vectorizer = new TokenCountVectorizer(new WordTokenizer());
    $vectorizer->fit($samples);
    $vectorizer->transform($samples);

    $tfIdfTransformer = new TfIdfTransformer();
    $tfIdfTransformer->fit($samples);
    $tfIdfTransformer->transform($samples);

    /* 3- gera os datasets de treino/teste
        gera um objeto dataset e divide ele em dois, sendo uma parte destinada
        pra treinar a IA e a outra pra testa-la
    */
    $dataset = new ArrayDataset($samples, $dataset->getTargets());
    $randomSplit = new StratifiedRandomSplit($dataset, 0.1);

    //dataset de treino
    $trainingSamples = $randomSplit->getTrainSamples();
    $trainingLabels = $randomSplit->getTrainLabels();

    //dataset de teste
    $testSamples = $randomSplit->getTestSamples();
    $testLabels = $randomSplit->getTestLabels();

    /* 4- treinar o classificador
        carrega os dados pra IA com as respostas pra ela poder treina, e ver
        se uma palavra ta mais atrelada a comentarios positivos ou negativos
        nota: o post que eu achei sobre ML simplesmente ESQUECEU de upar essa
        do codigo, entao essa etapa eh suposta
    */
    $analyzer = new analise();

    $analyzer->train($trainingSamples, $trainingLabels);

    /* 5- testar o classificador
        carrega os dados pra IA pra dessa vez ela, de fato, tentar prever se
        um tweet eh positivo ou negativo, com base no que ela ja aprendeu com
        ou outro dataset
    */
    $predictedLabels = $analyzer->predict($testSamples);

    //testa a precisao
    echo 'precisao: '.Accuracy::score($testLabels, $predictedLabels);
?>
