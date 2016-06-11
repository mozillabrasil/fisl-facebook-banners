<?php

    /**
    * TBD
    * - Definir tamanho limite para a imagem (tanto em MB quanto em PX)
    **/

    // Raiz da aplicação
    define('__ROOT__', dirname(__DIR__));

    // Valor inicial
    $valido = null;
    $foto = null;

    // Função para fazer quebra de linha
    function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth) {

        // Variáveis básicas
        $words = explode(" ", $text);
        $lines = array();
        $i = 0;
        $lineHeight = 0;

        // Para cada letra
        while ($i < count($words)) {

            // Inicia a palavra
            $currentLine = $words[$i];

            // Se cabe tudo em uma linha só
            if($i+1 >= count($words)) {

                // Manda brasa!
                $lines[] = [
                    'text' => $currentLine,
                    'width' => $image->queryFontMetrics($draw, $currentLine)['textWidth']
                ];
                break;

            }

            // Largura com mais uma palavra
            $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);

            // Se dá pra ir mais uma palavra
            while($metrics['textWidth'] <= $maxWidth) {

                // Adiciona a palavra na linha
                $currentLine .= ' ' . $words[++$i];
                if($i+1 >= count($words))
                    break;

                // E atualiza a largura
                $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);

            }

            // Adiciona essa última linha
            $lines[] = [
                'text' => $currentLine,
                'width' => $image->queryFontMetrics($draw, $currentLine)['textWidth']
            ];


            $i++;

            // Pega altura da maior linha
            if($metrics['textHeight'] > $lineHeight)
                $lineHeight = $metrics['textHeight'];

        }

        // E retorna tudo
        return ['lines' => $lines, 'height' => $lineHeight];

    }

    // Função para inserir as linhas nos boxes
    function insertLines($imagem, $draw, $coordenadas, $limiteLinhas, $linhas) {

        // Formata variáveis de acordo com as linhas
        $linhacounter = 0;
        $coordenadas['y'] = $coordenadas['y'] + ($linhas['height'] / 4);
        $linhas['lines'] = array_slice($linhas['lines'], 0, $limiteLinhas);

        // Para cada linha do nome do palestrante
        foreach ($linhas['lines'] as $linha) {

            // Seta o local
            $y = $coordenadas['y'] + ($linhacounter * $linhas['height']);
            $x = (($coordenadas['w'] - $linha['width']) / 2) + $coordenadas['x'];

            // Insere ela no banner
            $imagem->annotateImage($draw, $x, $y, 0, $linha['text']);
            $linhacounter++;

        }

    }

    // Algo foi enviado
    if ((count($_FILES) > 0) && (count($_POST) > 0) && (isset($_FILES['foto']))) {

        // Validar
        $required = ['palestra', 'palestrante'];
        $valido = true;

        // Para cada requisito
        foreach ($required as $requisito) {

            // Se não foi definido
            if (!isset($_POST[$requisito])) {

                // Invalidar solicitação
                $valido = false;
                break;

            // Formatar
            } else {

                // Remove espaços adicionais
                $_POST[$requisito] = trim($_POST[$requisito]);

            }

        }

    }

    // Este é um request válido
    if ($valido === true) {

        $_POST['palestra'] = 'Palestra: ' . ucfirst(strtolower($_POST['palestra']));
        $_POST['palestrante'] = ucwords(strtolower($_POST['palestrante']));

        // Caixa do nome
        $nomebox = [
            'w' => 329,
            'x' => 367,
            'y' => 406,
        ];

        // Caixa da palestra
        $palestrabox = [
            'w' => 384,
            'x' => 337,
            'y' => 520,
        ];

        // Abre o banner
        $banner = new Imagick();
        $banner->readImage(__ROOT__ . '/modelo.png');

        // Abre a foto
        $foto = new Imagick();
        $foto->readImage($_FILES['foto']['tmp_name']);

        // Pega informações da foto
        $fotoData = [];
        $fotoData['w'] = $foto->getImageWidth();
        $fotoData['h'] = $foto->getImageHeight();

        // Imagem é mais larga
        if ($fotoData['w'] > $fotoData['h']) {

            // Pega a margem
            $diferenca = ($fotoData['w'] - $fotoData['h']) / 2;

            // Corta
            $foto->cropImage($fotoData['h'], $fotoData['h'], $diferenca, 0);

        // Imagem é mais alta
        } else if ($fotoData['h'] > $fotoData['w']) {

            // Pega a margem
            $diferenca = ($fotoData['h'] - $fotoData['w']) / 2;

            // Corta
            $foto->cropImage($fotoData['w'], $fotoData['w'], 0, $diferenca);

        }

        // Redimensiona
        $foto->scaleImage(245, 0);

        // Insere a foto no banner
        $banner->compositeImage($foto, imagick::COMPOSITE_OVER, 57, 324);

        // Escreve o nome
        $palestrante = new ImagickDraw();
        $palestrante->setFillColor('white');
        $palestrante->setFont(__ROOT__ . '/fonts/PaytoneOne.ttf');
        $palestrante->setFontSize( 40 );

        // Escreve a palestra
        $palestra = new ImagickDraw();
        $palestra->setFillColor('white');
        $palestra->setFont(__ROOT__ . '/fonts/PaytoneOne.ttf');
        $palestra->setFontSize( 20 );

        // Pega a lista de linhas formatadas
        $palestranteLines = wordWrapAnnotation($banner, $palestrante, $_POST['palestrante'], $nomebox['w']);
        $palestraLines = wordWrapAnnotation($banner, $palestra, $_POST['palestra'], $palestrabox['w']);

        insertLines($banner, $palestrante, $nomebox, 2, $palestranteLines);
        insertLines($banner, $palestra, $palestrabox, 3, $palestraLines);

    }

?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="/favicon.ico">
        <title>#FISL17 - Gerador de Banners</title>

        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">

        <style>
            input {margin-bottom: 10px;}
            h1.done {margin-bottom: 20px;}
            #download {margin: 20px 0;}
            #logo {margin-top: 10px;}
        </style>

        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container text-center">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                <?php if (isset($banner)) { ?>

                    <h1 class="done">Olá <strong><?php echo $_POST['palestrante'] ?></strong>, aqui está o seu banner</h1>
                    <img alt="<?php echo $_POST['palestrante']; ?> no #FISL17" id="img" class="img-responsive" src="data:image/jpg;base64,<?php echo base64_encode( $banner->getImageBlob() ); ?>">
                    <button download="banner-fisl.jpg" class="btn btn-success btn-block btn-lg" id="download"> Fazer Download</button>

                <?php } else { ?>

                    <img src="/fisl.png" alt="#FISL17" id="logo">
                    <h1><strong>#FISL17</strong></h1>
                    <h2>Gerador de banners</h2>

                    <p>Preencha todos os campos abaixo e deixe que nós cuidamos do resto</p>

                    <form action="/" method="post" enctype="multipart/form-data">
                        <input type="text" class="form-control" name="palestrante" placeholder="Seu nome" required>
                        <input type="text" class="form-control" name="palestra" placeholder="Nome da Palestra" required>
                        <label>Sua foto: <input type="file" name="foto" required></label>
                        <input type="submit" class="btn btn-success btn-block btn-lg" name="Enviar">
                    </form>

                <?php } ?>
                </div>
            </div>
        </div>

        <script>
            // Pega a imagem e o botão
            var img = document.getElementById('img');
            var button = document.getElementById('download');

            // Ao clicar no botão
            button.onclick = function() {
                location.href = img.src; // <-- Download!
            };
        </script>
    </body>
</html>
