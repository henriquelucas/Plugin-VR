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

// Função para adicionar CDN do Chart.js
function adicionar_chartjs_cdn() {
    // Adiciona o CDN do Chart.js apenas na página de vendas
    if (isset($_GET['page']) && $_GET['page'] === 'vr-vendas') {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '2.9.4');
    }
}
add_action('admin_enqueue_scripts', 'adicionar_chartjs_cdn');

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

    // Submenu - Preço
    add_submenu_page(
        'vr-pagina', // Slug da página pai
        'Preço', // Título da página
        'Preço', // Nome do menu
        'manage_options', // Capacidade necessária para acessar
        'vr-preco', // Slug da página
        'vr_preco_conteudo' // Callback da função para exibir o conteúdo da página
    );

    add_submenu_page(
        'vr-pagina', // Slug da página pai
        'DSV', // Título da página
        'DSV', // Nome do menu
        'manage_options', // Capacidade necessária para acessar
        'vr-dsv', // Slug da página
        'vr_dsv_conteudo' // Callback da função para exibir o conteúdo da página
    );
}
add_action('admin_menu', 'vr_adicionar_paginas');

// Conteúdo da página principal
function vr_pagina_conteudo() {
    ?>
    <div class="wrap bg-white border">
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
           // Prepara a consulta SQL para o período atual
           $stmt_atual = $conexao_postgresql->prepare("
               SELECT SUM(subtotalimpressora) - SUM(valordesconto) + SUM(valoracrescimo) as resultado, COUNT(*) as total_vendas
               FROM pdv.venda
               WHERE data >= ? AND data <= ?
               AND cancelado = false
               AND canceladoemvenda = false
               AND id_loja = ?
           ");
   
           // Executa a consulta para o período atual
           $stmt_atual->execute(array($data_inicial, $data_final, get_current_user_id()));
   
           // Obtém o resultado do período atual
           $resultado_atual = $stmt_atual->fetch(PDO::FETCH_ASSOC);
   
           // Formata os resultados para exibir no formato de moeda
           $resultado_atual_formatado = number_format($resultado_atual['resultado'], 2, ',', '.'); // Formato: R$ 40.251,89
           $ticket_medio_formatado = number_format($ticket_medio, 2, ',', '.'); // Formato: R$ 40.251,89
   
           // Prepara a consulta SQL para o período do mês anterior
           $data_inicial_mes_passado = date('Y-m-d', strtotime('-1 month', strtotime($data_inicial)));
           $data_final_mes_passado = date('Y-m-d', strtotime('-1 month', strtotime($data_final)));
           $stmt_mes_passado = $conexao_postgresql->prepare("
               SELECT SUM(subtotalimpressora) - SUM(valordesconto) + SUM(valoracrescimo) as resultado
               FROM pdv.venda
               WHERE data >= ? AND data <= ?
               AND cancelado = false
               AND canceladoemvenda = false
               AND id_loja = ?
           ");
   
           // Executa a consulta para o período do mês anterior
           $stmt_mes_passado->execute(array($data_inicial_mes_passado, $data_final_mes_passado, get_current_user_id()));
   
           // Obtém o resultado do período do mês anterior
           $resultado_mes_passado = $stmt_mes_passado->fetchColumn();
   
           // Formata o resultado do mês anterior para exibir no formato de moeda
           $resultado_mes_passado_formatado = number_format($resultado_mes_passado, 2, ',', '.'); // Formato: R$ 40.251,89
   
           // Exibe o formulário para inserir as datas
           ?>
        <div class="wrap">
            <h1>Vendas</h1>
            <form method="post" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="data_inicial" class="form-label">Data Inicial:</label>
                    <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?= $data_inicial ?>" required />
                </div>
                <div class="col-md-6">
                    <label for="data_final" class="form-label">Data Final:</label>
                    <input type="date" class="form-control" id="data_final" name="data_final" value="<?= $data_final ?>" required />
                </div>
                <div class="col-12">
                    <button type="submit" name="submit_dates" class="btn btn-primary">Consultar Vendas</button>
                </div>
            </form>

            <?php
            // Se o formulário foi enviado, exibe o resultado da consulta
            if (isset($_POST['submit_dates'])) {
                ?>
                <div class="row mt-3">
                    <div class="col-sm-4">
                        <div class="card alert alert-success">
                            <div class="card-body">
                                <h5 class="card-title">Total Vendas Líquidas</h5>
                                <h6>(Período Atual)</h6>
                                <h4 class="card-text">R$ <?= $resultado_atual_formatado ?></h4>
                            </div>
                        </div>
                    </div>
                
                    <div class="col-sm-4">
                        <div class="card alert alert-info">
                            <div class="card-body">
                                <h5 class="card-title">Total Vendas Líquidas</h5>
                                <h6>(Mês Anterior)</h6>
                                <h4 class="card-text">R$ <?= $resultado_mes_passado_formatado ?></h4>
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


// Função Preço
//Exibira uma pagina com as consulta de preços de produtos por codigo de barras e cod interno.
function vr_preco_conteudo() {
    // Estabelece a conexão com o banco de dados PostgreSQL
    $conexao_postgresql = vr_estabelecer_conexao_postgresql();

    // Define os valores padrão do código de barras e do ID do produto
    $codigo_barras = '';
    $id_produto = '';

    // Verifica se o formulário foi enviado
    if (isset($_POST['submit_consulta_preco'])) {
        // Verifica se foi fornecido o código de barras ou o ID do produto
        if (!empty($_POST['codigo_barras']) || !empty($_POST['id_produto'])) {
            $codigo_barras = sanitize_text_field($_POST['codigo_barras']);
            $id_produto = sanitize_text_field($_POST['id_produto']);
            $id_loja = sanitize_text_field($_POST['id_loja']);

            // Define a consulta com base no valor fornecido
            if (!empty($codigo_barras)) {
                $consulta = "pa.codigobarras = ?";
                $valor_consulta = $codigo_barras;
            } elseif (!empty($id_produto)) {
                $consulta = "pa.id_produto = ?";
                $valor_consulta = $id_produto;
            }

            // Consulta ao banco de dados
            try {
                // Prepara a consulta SQL
                $stmt = $conexao_postgresql->prepare("
                    SELECT pa.id_produto, pc.precovenda, pc.precodiaseguinte, pc.dataultimavenda, pc.estoque, p.descricaocompleta
                    FROM produtoautomacao pa
                    JOIN produtocomplemento pc ON pa.id_produto = pc.id_produto
                    JOIN produto p ON pa.id_produto = p.id
                    WHERE $consulta AND pc.id_loja = ?
                ");

                // Executa a consulta
                $stmt->execute(array($valor_consulta, $id_loja));

                // Obtém o resultado
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

                // Exibe o resultado
                if ($resultado) {
                    echo '<div class="wrap bg-white border">';
echo '<h1>Consulta Preço</h1>';
echo '<form method="post" action="">';
echo '<table class="table">'; // Adicionando a tabela Bootstrap
echo '<tr>'; // Iniciando uma nova linha
echo '<td>'; // Coluna 1
echo '<div class="mb-3">';
echo '<label for="codigo_barras" class="form-label">Código de Barras:</label>';
echo '<input type="text" class="form-control" id="codigo_barras" name="codigo_barras" value="' . $codigo_barras . '">';
echo '</div>';
echo '</td>'; // Fim da coluna 1
echo '<td>'; // Coluna 2
echo '<div class="mb-3">';
echo '<label for="id_produto" class="form-label">ID do Produto:</label>';
echo '<input type="text" class="form-control" id="id_produto" name="id_produto" value="' . $id_produto . '">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label for="id_loja" class="form-label">Loja:</label>';
echo '<select class="form-select" id="id_loja" name="id_loja">';
echo '<option value="1">1</option>'; // Opção padrão selecionada
echo '<option value="2">2</option>';
echo '</select>';
echo '</div>';
echo '</td>'; // Fim da coluna 2
echo '</tr>'; // Fim da linha
echo '</table>'; // Fim da tabela
echo '<input type="submit" name="submit_consulta_preco" value="Consultar Preço" class="btn btn-primary">';
echo '</form>';

// Resultado dentro de uma tabela Bootstrap
echo '<h1>Consulta Preço</h1>';
echo '<table class="table">'; // Adicionando a tabela Bootstrap
echo '<tr>'; // Iniciando uma nova linha
echo '<td><strong>Descrição:</strong></td>'; // Coluna 1
echo '<td>' . $resultado['descricaocompleta'] . '</td>'; // Coluna 2
echo '</tr>'; // Fim da linha
echo '<tr>'; // Iniciando uma nova linha
echo '<td><strong>Preço de Venda:</strong></td>'; // Coluna 1
echo '<td>R$ ' . number_format($resultado['precovenda'], 2, ',', '.') . '<div class="">Altera Preço: <input type="text" /></div></td>'; // Coluna 2
echo '</tr>'; // Fim da linha
echo '<tr>'; // Iniciando uma nova linha
echo '<td><strong>Preço do Dia Seguinte:</strong></td>'; // Coluna 1
echo '<td>R$ ' . number_format($resultado['precodiaseguinte'], 2, ',', '.') . '</td>'; // Coluna 2
echo '</tr>'; // Fim da linha
echo '<tr>'; // Iniciando uma nova linha
echo '<td><strong>Estoque:</strong></td>'; // Coluna 1
echo '<td>' . $resultado['estoque'] . '</td>'; // Coluna 2
echo '</tr>';
echo '<tr>'; // Iniciando uma nova linha
echo '<td><strong>Última venda:</strong></td>'; // Coluna 1
echo '<td>' . $resultado['dataultimavenda'] . '</td>'; // Coluna 2
echo '</tr>'; // Fim da linha
echo '</table>'; // Fim da tabela

echo '</div>'; // Fim do wrap

                } else {
                    echo '<div class="wrap">';
                    echo '<h1>Consulta Preço</h1>';
                    echo '<p>Nenhum resultado encontrado para ';
                    echo !empty($codigo_barras) ? 'o código de barras: ' . $codigo_barras : 'o ID do produto: ' . $id_produto;
                    echo '</p>';
                    echo '</div>';
                }
            } catch (PDOException $e) {
                // Em caso de erro, exibe uma mensagem de erro
                echo "Erro ao executar a consulta: " . $e->getMessage();
            }
        } else {
            echo '<div class="wrap">';
            echo '<h1>Consulta Preço</h1>';
            echo '<p>Por favor, insira o código de barras ou o ID do produto.</p>';
            echo '</div>';
        }
    } else {
        // Exibe o formulário de consulta de preço
        ?>
        <div class="wrap">
            <h1>Consulta Preço</h1>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="codigo_barras" class="form-label">Código de Barras:</label>
                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" value="<?= $codigo_barras ?>">
                </div>
                <div class="mb-3">
                    <label for="id_produto" class="form-label">ID do Produto:</label>
                    <input type="text" class="form-control" id="id_produto" name="id_produto" value="<?= $id_produto ?>">
                </div>

                <div class="mb-3">
                    <label for="id_loja" class="form-label">Loja:</label>
                    <select class="form-select" id="id_loja" name="id_loja">
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>

                <input type="submit" name="submit_consulta_preco" value="Consultar Preço" class="btn btn-primary">
            </form>
        </div>
        <?php
    }
}

function vr_dsv_conteudo() { 
    // Verifica se o formulário foi enviado
    if (isset($_POST['submit_dsv'])) {
        // Obtém o número de dias sem venda especificado pelo usuário
        $dias_sem_venda = isset($_POST['dias_sem_venda']) ? intval($_POST['dias_sem_venda']) : 0;
    
        // Estabelece a conexão com o banco de dados PostgreSQL
        $conexao_postgresql = vr_estabelecer_conexao_postgresql();
    
        // Define a consulta SQL
        $consulta = "1=1"; // Consulta geral, sem condição específica de código de barras
    
        $id_loja = '1';
        // Consulta ao banco de dados
        try {
            // Prepara a consulta SQL
            $stmt = $conexao_postgresql->prepare("
            SELECT DISTINCT pa.id_produto, 
                   pc.precovenda, 
                   pc.precodiaseguinte, 
                   pc.dataultimavenda, 
                   pc.estoque, 
                   p.descricaocompleta,
                   CURRENT_DATE - pc.dataultimavenda AS dias_sem_venda
            FROM produtoautomacao pa
            JOIN produtocomplemento pc ON pa.id_produto = pc.id_produto
            JOIN produto p ON pa.id_produto = p.id
            WHERE pc.dataultimavenda <= CURRENT_DATE - INTERVAL '5 days' 
            AND pc.dataultimavenda >= CURRENT_DATE - INTERVAL '{$dias_sem_venda} days'
            AND pc.id_loja = ? AND pc.estoque > 0
            ORDER BY dias_sem_venda DESC
        ");
        
    
            // Executa a consulta
            $stmt->execute(array($id_loja));
    
            // Obtém os resultados da consulta
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Exibe os resultados da consulta
            if ($resultados) {
                // Exibe os resultados em uma tabela
                echo '<div class="wrap bg-white border">';
                echo '<div class="row">';
                echo '<div class="col-sm-6">';
                echo '<h1>Relatório de DSV (Dias Sem Venda)</h1>';
                echo '</div>';
                echo '<div class="col-sm-6">';
                echo '<button id="imprimirBtn">Imprimir</button>';
                echo '</div>';
                echo '<table id="tabelaResultados" class="table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>ID do Produto</th>';
                echo '<th>Descrição</th>';
                echo '<th>Preço de Venda</th>';
                echo '<th>Data Última Venda</th>';
                echo '<th>Estoque</th>';
                echo '<th>Dias Sem Venda</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($resultados as $resultado) {
                    // Formata os preços
                    $preco_venda_formatado = 'R$ ' . number_format($resultado['precovenda'], 2, ',', '.');
                    $data_ultima_venda_formatada = date('d/m/Y', strtotime($resultado['dataultimavenda']));
                    
                    // Exibe os resultados na tabela
                    echo '<tr>';
                    echo '<td>' . $resultado['id_produto'] . '</td>';
                    echo '<td>' . $resultado['descricaocompleta'] . '</td>';
                    echo '<td>' . $preco_venda_formatado . '</td>';
                    echo '<td>' . $data_ultima_venda_formatada . '</td>';
                    echo '<td>' . $resultado['estoque'] . '</td>';
                    echo '<td>' . $resultado['dias_sem_venda'] . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="wrap">';
                echo '<h1>Relatório de DSV (Dias Sem Venda)</h1>';
                echo '<p>Nenhum produto encontrado com mais de ' . $dias_sem_venda . ' dias sem venda ou estoque disponível.</p>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            // Em caso de erro, exibe uma mensagem de erro
            echo "Erro ao executar a consulta: " . $e->getMessage();
        }
    }
    
    // Exibe o formulário para o usuário especificar o número de dias sem venda
    ?>

    
<script>
document.getElementById("imprimirBtn").addEventListener("click", function() {
    // Seleciona a tabela
    var tabela = document.getElementById("tabelaResultados");
    // Cria uma janela de impressão
    var janela = window.open('', '', 'height=600,width=800');
    janela.document.write('<html><head><title>Imprimir</title></head><body>');
    // Adiciona o conteúdo da tabela à janela de impressão
    janela.document.write(tabela.outerHTML);
    janela.document.write('</body></html>');
    // Fecha a janela após a impressão
    janela.document.close();
    janela.print();
});
</script>
    <div class="wrap bg-white border">
        <h1>Relatório de DSV (Dias Sem Venda)</h1>
        <form method="post" action="">
            <label for="dias_sem_venda">Até quantos dias sem venda?</label></br>
            <input type="number" id="dias_sem_venda" name="dias_sem_venda" min="0" value="5"></br></br>
            <input class="btn btn-primary" type="submit" name="submit_dsv" value="Consultar">
        </form>
    </div>
    <?php
}

