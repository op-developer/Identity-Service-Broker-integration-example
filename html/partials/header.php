<!DOCTYPE HTML>
<html>
    <head>
        <meta charset=UTF-8>
        <title><?= $page['title'] ?></title>
        <link href="https://fonts.googleapis.com/css?family=Oswald|PT+Sans"
              rel="stylesheet">
        <link href="/style.css" rel="stylesheet">
    </head>

    <body>
        <div class="container">
            <header>
                <h1>Demo Service Provider</h1>
                <?php if ($page['type'] == 'example') :
                    $exampleItems = array(
                        0 => array(
                            'name' => '/index.php',
                            'label' => 'OP\'s hosted identification UI'
                            ),
                        1 => array(
                            'name' => '/embedded.php',
                            'label' => 'Self-hosted UI (buttons) embedded into your service'
                            ),
                        2 => array(
                            'name' => '/embedded2.php',
                            'label' => 'Self-hosted UI (dropdown) embedded into your service'
                            ),
                    ); ?>
                    <p>
                        OP Identity Service Broker allows Service Providers to implement strong electronic
                        identification (Finnish bank credentials, Mobile ID) easily to websites and mobile apps via
                        single API.
                    </p>
                    <p>
                        This Demo Service Provider gives you three different examples how to integrate to the
                        OP Identity Service Broker:
                    </p>
                    <ol>
                        <?php foreach($exampleItems as $item) { ?>
                            <li>
                                <?php if ($item['name'] == $_SERVER['PHP_SELF']) : ?>
                                    <?= $item['label']?>
                                <?php else: ?>
                                    <a href="<?= $item['name']?>"><?= $item['label']?></a>
                                <?php endif; ?>
                            </li>
                        <?php } ?>
                    </ol>
                <?php endif; ?>
            </header>
