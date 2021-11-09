# Unienvios - Módulo 2

Esse módulo é responsável por executar a ação de enviar os dados do pedido para a API quando o status do pedido for alterado para "processando".

# Instalação
- Copie o conteúdo do repositório para <b>app/code/Unienvios/SendCotacao</b>
- Execute o comando: <b>php bin/magento setup:upgrade</b>
- Execute o comando: <b>php bin/magento setup:static-content:deploy pt_BR en_US -f
</b>  (Use -f for force deploy on 2.2.x or later)
- Agora limpe a cache <b>php bin/magento cache:flush</b>
