<?php
/*
Plugin Name: VR SOFTWARE
Description: Plugin que integra alguns funções do VR Software ao Wordpress
Version: 1.0
Author: Henrique Lucas de Sousa
*/

// Função para carregar os estilos e scripts do Bootstrap via CDN apenas nas páginas de administração do WordPress
function carregar_bootstrap_cdn_no_admin() {
    // Verifica se estamos na área de administração do WordPress
    if (is_admin()) {
        // Enfileira o arquivo CSS do Bootstrap via CDN
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');

        // Enfileira o arquivo JavaScript do Bootstrap via CDN
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js', array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'carregar_bootstrap_cdn_no_admin');

// Registra e enfileira o estilo do plugin
function vr_registrar_estilo() {
    wp_register_style( 'vr-software-estilo', plugins_url( 'css/style.css', __FILE__ ) );
    wp_enqueue_style( 'vr-software-estilo' );
}
add_action( 'admin_enqueue_scripts', 'vr_registrar_estilo' );


// Registra o gancho de ativação do plugin
register_activation_hook(__FILE__, 'vr_ativar_plugin');

// Registra o gancho de desativação do plugin
register_deactivation_hook(__FILE__, 'vr_desativar_plugin');

// Função para criar a tabela vrconfig no banco de dados
function vr_criar_tabela_vrconfig() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vrconfig';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        pg_host VARCHAR(100) NOT NULL,
        pg_port VARCHAR(10) NOT NULL,
        pg_database VARCHAR(100) NOT NULL,
        pg_user VARCHAR(100) NOT NULL,
        pg_password VARCHAR(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function vr_estabelecer_conexao_postgresql() {
    global $wpdb;

    // Nome da tabela vrconfig
    $table_name = $wpdb->prefix . 'vrconfig';

    // Consulta SQL para recuperar os dados de conexão com o banco de dados PostgreSQL
    $sql = "SELECT pg_host, pg_port, pg_database, pg_user, pg_password FROM $table_name";

    // Executa a consulta
    $dados_conexao = $wpdb->get_row($sql, ARRAY_A);

    // Verifica se os dados foram recuperados com sucesso
    if ($dados_conexao) {
        try {
            // Constrói a string de conexão DSN
            $dsn = "pgsql:host=" . $dados_conexao['pg_host'] . ";port=" . $dados_conexao['pg_port'] . ";dbname=" . $dados_conexao['pg_database'];

            // Cria uma nova conexão PDO
            $pdo = new PDO($dsn, $dados_conexao['pg_user'], $dados_conexao['pg_password']);

            // Define o modo de erro para lançar exceções em caso de erro
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (PDOException $e) {
            // Em caso de erro, exibe uma mensagem de erro
            die("Erro ao conectar-se ao banco de dados PostgreSQL: " . $e->getMessage());
        }
    } else {
        // Trate o caso em que os dados de conexão não puderam ser recuperados
        die("Erro ao recuperar dados de conexão com o banco de dados PostgreSQL.");
    }
}

// Função para ativar o plugin
function vr_ativar_plugin() {
    // Cria a tabela vrconfig no banco de dados
    vr_criar_tabela_vrconfig();
}

// Função para desativar o plugin
function vr_desativar_plugin() {
    // Não é necessário fazer nada na desativação do plugin neste exemplo
}

// Adiciona uma página ao menu do WordPress
function vr_adicionar_paginas() {
    // Página principal
    add_menu_page(
        'VR',
        'VR',
        'manage_options',
        'vr-pagina',
        'vr_pagina_conteudo',
        'dashicons-admin-generic', // Ícone opcional, troque pelo slug do ícone desejado
        20 // Prioridade
    );
    
    // Submenu - Vendas
    add_submenu_page(
        'vr-pagina', // Slug da página pai
        'Vendas', // Título da página
        'Vendas', // Nome do menu
        'manage_options', // Capacidade necessária para acessar
        'vr-vendas', // Slug da página
        'vr_vendas_conteudo' // Callback da função para exibir o conteúdo da página
    );
}
add_action('admin_menu', 'vr_adicionar_paginas');

// Conteúdo da página principal
function vr_pagina_conteudo() {
    ?>
    <div class="wrap">
        <h1>Bem Vindo ao VR Software</h1>
        <p>Aqui está o conteúdo da sua página principal do plugin.</p>
        
        <h2>Configuração do Banco de Dados PostgreSQL</h2>
        <div class="alert alert-primary" role="alert">
            Para integrar ao banco de dados do vr é necessário que você configure um IP real para e liberar a porta 8745 
        </div>
        <form method="post" action="">
            <label for="pg_host">Host:</label></br>
            <input type="text" id="pg_host" name="pg_host" value="<?php echo esc_attr(get_option('pg_host')); ?>" /><br/>
            </br>
            <label for="pg_port">Porta:</label></br>
            <input type="text" id="pg_port" name="pg_port" value="<?php echo esc_attr(get_option('pg_port')); ?>" /><br/>
            </br>
            <label for="pg_database">Nome do Banco de Dados:</label></br>
            <input type="text" id="pg_database" name="pg_database" value="<?php echo esc_attr(get_option('pg_database')); ?>" /><br/>
            </br>
            <label for="pg_user">Usuário:</label></br>
            <input type="text" id="pg_user" name="pg_user" value="<?php echo esc_attr(get_option('pg_user')); ?>" /><br/>
            </br>
            <label for="pg_password">Senha:</label></br>
            <input type="password" id="pg_password" name="pg_password" value="<?php echo esc_attr(get_option('pg_password')); ?>" /><br/>
            </br>
            <input type="submit" name="submit_pg_config" value="Salvar Configurações" class="button button-primary" />
        </form>
    </div>
    <?php
}

// Verifica se o formulário foi enviado e salva as configurações do banco de dados PostgreSQL
if (isset($_POST['submit_pg_config'])) {
    update_option('pg_host', $_POST['pg_host']);
    update_option('pg_port', $_POST['pg_port']);
    update_option('pg_database', $_POST['pg_database']);
    update_option('pg_user', $_POST['pg_user']);
    update_option('pg_password', $_POST['pg_password']);

    // Salva também as configurações no banco de dados
    global $wpdb;
    $table_name = $wpdb->prefix . 'vrconfig';
    $wpdb->replace($table_name, array(
        'pg_host' => $_POST['pg_host'],
        'pg_port' => $_POST['pg_port'],
        'pg_database' => $_POST['pg_database'],
        'pg_user' => $_POST['pg_user'],
        'pg_password' => $_POST['pg_password']
    ));
}

// Conteúdo da página de vendas
// Conteúdo da página de vendas
function vr_vendas_conteudo() {
    // Estabelece a conexão com o banco de dados PostgreSQL
    $conexao_postgresql = vr_estabelecer_conexao_postgresql();

    // Define as datas inicial e final como a data de hoje
    $data_inicial = date('Y-m-d');
    $data_final = date('Y-m-d');

    // Verifica se o formulário foi enviado e obtém as datas fornecidas
    if (isset($_POST['submit_dates'])) {
        $data_inicial = sanitize_text_field($_POST['data_inicial']);
        $data_final = sanitize_text_field($_POST['data_final']);
    }

    try {
        // Prepara a consulta SQL
        $stmt = $conexao_postgresql->prepare("
            SELECT SUM(subtotalimpressora) - SUM(valordesconto) + SUM(valoracrescimo) as resultado
            FROM pdv.venda
            WHERE data >= ? AND data <= ?
            AND cancelado = false
            AND canceladoemvenda = false
            AND id_loja = ?
        ");

        // Executa a consulta
        $stmt->execute(array($data_inicial, $data_final, get_current_user_id()));

        // Obtém o resultado
        $resultado = $stmt->fetchColumn();

        // Formata o resultado para exibir no formato de moeda
        $resultado_formatado = number_format($resultado, 2, ',', '.'); // Formato: R$ 40.251,89

        // Exibe o formulário para inserir as datas
        ?>
        <div class="wrap">
            <h1>Vendas</h1>
            <form method="post" action="">
                <label for="data_inicial">Data Inicial:</label>
                <input type="date" id="data_inicial" name="data_inicial" value="<?= $data_inicial ?>" required />
                <label for="data_final">Data Final:</label>
                <input type="date" id="data_final" name="data_final" value="<?= $data_final ?>" required />
                <input type="submit" name="submit_dates" value="Consultar Vendas" class="button button-primary" />
            </form>

            <?php
            // Se o formulário foi enviado, exibe o resultado da consulta
            if (isset($_POST['submit_dates'])) {
                ?>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Total Vendas Líquidas:</h4>
                                <h4 class="card-text">R$ <?= $resultado_formatado ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    } catch (PDOException $e) {
        // Em caso de erro, exibe uma mensagem de erro
        echo "Erro ao executar a consulta: " . $e->getMessage();
    }
}
