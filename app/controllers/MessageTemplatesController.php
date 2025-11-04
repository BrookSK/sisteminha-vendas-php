<?php
namespace Controllers;

use Core\Controller;

class MessageTemplatesController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','organic','manager','admin']);

        $userName = (string) ((\Core\Auth::user()['name'] ?? \Core\Auth::user()['email'] ?? ''));

        $templates = [
            'Atendimento inicial / saudação' => [
                [
                    'id' => 'saudacao_simples',
                    'titulo' => 'Padrão 1 — Saudação simples',
                    'texto' => 'Oi [Nome], tudo bem? Aqui é a [Nome do Usuario do sistema] do time da Braziliana. Como eu posso te ajudar?',
                    'campos' => [
                        ['chave' => 'Nome', 'rotulo' => 'Nome do cliente'],
                        ['chave' => 'Nome do Usuario do sistema', 'rotulo' => 'Seu nome'],
                    ],
                ],
                [
                    'id' => 'encaminhamento_suporte',
                    'titulo' => 'Padrão 2 — Encaminhamento para suporte',
                    'texto' => 'Oie, tudo bem? Chama meu time no suporte do WhatsApp que eles orientam você certinho sobre como comprar, informações sobre valores, pagamentos e todo o processo! 🥰\n🔗 Link do WhatsApp: https://api.whatsapp.com/send?phone=13053638204\n\n📞 Número: +1 (305) 363-8204',
                    'campos' => [],
                ],
            ],
            'Informações gerais sobre o serviço' => [
                [
                    'id' => 'como_funciona',
                    'titulo' => 'Como funciona o serviço',
                    'texto' => 'Nosso serviço funciona assim:\nVocê pode comprar os produtos sozinha, enviá-los pra nossa sede e nós redirecionamos pra você no Brasil.\nCaso prefira, podemos te ajudar a comprar online também.\nNós calculamos o peso total pra saber a taxa de serviço — são $39 dólares por quilo da caixa enviada pro Brasil, e o frete é sempre GRÁTIS!\nSuper simples e descomplicado!\n\nCaso queira comprar nos nossos grupos de compra ou produtos já cadastrados, é só acessar o Braziliana Shop neste link:\n🔗 https://br.brazilianashop.com.br',
                    'campos' => [],
                ],
                [
                    'id' => 'limites_dimensoes',
                    'titulo' => 'Limites e dimensões da caixa',
                    'texto' => 'Você pode enviar caixas de até 30 kg cada.\nNenhum lado da caixa pode ultrapassar 38 inches (99 cm) e a soma dos três lados não pode ultrapassar 78 inches (1,90 m).\nFora isso, tudo certinho! 📦',
                    'campos' => [],
                ],
                [
                    'id' => 'processo_completo',
                    'titulo' => 'Processo completo (passo a passo)',
                    'texto' => 'Você primeiro faz seu cadastro no nosso site. Assim que fizer, terá acesso ao endereço da nossa sede.\nAí você pode comprar o que quiser e enviar pra esse endereço.\nQuando terminar de comprar tudo, me avisa!\nA Fabi vai pesar sua caixa e calcular a taxa de serviço (US$39 por kg).\nO frete é grátis! 🚀\n\nUma vez com tudo pago, a Fabi leva sua caixa pro aeroporto. O envio costuma ocorrer em até 2 semanas.\nDepois que sai dos EUA, os Correios levam em média 10 a 30 dias úteis pra entregar no Brasil.',
                    'campos' => [],
                ],
            ],
            'Pagamento e taxas' => [
                [
                    'id' => 'sobre_parcelamento',
                    'titulo' => 'Sobre parcelamento',
                    'texto' => 'Tanto o valor do(s) produto(s) quanto a taxa de serviço podem ser parcelados em até 12 vezes no cartão (com acréscimo a partir da 1ª parcela), ou pagos via Pix ou boleto.',
                    'campos' => [],
                ],
                [
                    'id' => 'parcelamento_impostos',
                    'titulo' => 'Sobre parcelamento de impostos',
                    'texto' => 'No cartão dá pra parcelar em até 12 vezes (com acréscimo a partir da 1ª parcela), Pix ou boleto.\nNós também oferecemos uma opção de parcelamento dos impostos — mesmo sendo uma cobrança da Receita Federal, fazemos o possível pra ajudar nossos clientes a terem acesso aos produtos que desejam.',
                    'campos' => [],
                ],
                [
                    'id' => 'baixa_manual',
                    'titulo' => 'Mensagem de baixa manual',
                    'texto' => 'Você vai receber um e-mail com um QR code pra fazer o pagamento.\nPode desconsiderar. Já está tudo pago e eu vou dar baixa manualmente. ✅',
                    'campos' => [],
                ],
            ],
            'Impostos e importação' => [
                [
                    'id' => 'explicacao_impostos',
                    'titulo' => 'Explicação dos impostos',
                    'texto' => 'Todas as caixas que entram no Brasil pagam imposto de importação que é 60% do valor do produto + preço do frete + 20% de ICMS.\nComo o frete é grátis pra todo mundo, o imposto acaba saindo mais em conta.\n\nOs impostos são pagos diretamente pra Receita Federal através de um link que ela disponibiliza pra você.',
                    'campos' => [],
                ],
            ],
            'Produtos específicos' => [
                [
                    'id' => 'iphone',
                    'titulo' => 'iPhone',
                    'texto' => 'Enviamos sim 📱\nFica o preço do aparelho + nossa taxa de serviço de US$39 por quilo.\nNo caso do iPhone, como não passa de 1 quilo, fica US$39 mesmo.\n\nTodas as caixas que entram no Brasil pagam imposto de 60% + 20% de ICMS (como explicado acima).\nO pagamento é feito diretamente à Receita Federal pelo link que ela envia.',
                    'campos' => [],
                ],
            ],
            'Mudança de planos / novos valores' => [
                [
                    'id' => 'sem_planos_fixos',
                    'titulo' => 'Nova política (sem planos fixos)',
                    'texto' => 'Oi [Nome], bom dia, tudo bem?\nAqui é a [Nome do Usuario do sistema] do time da Braziliana.\nNós passamos por uma transição: não temos mais planos de assinatura.\nAgora a Braziliana trabalha com um valor fixo de US$39 por quilo de caixa enviada pro Brasil.\nMuito mais simples! 💪',
                    'campos' => [
                        ['chave' => 'Nome', 'rotulo' => 'Nome do cliente'],
                        ['chave' => 'Nome do Usuario do sistema', 'rotulo' => 'Seu nome'],
                    ],
                ],
            ],
            'Invoice / declaração aduaneira' => [
                [
                    'id' => 'liberacao_invoice',
                    'titulo' => 'Assunto: Liberação de Invoice – Declaração Aduaneira',
                    'texto' => 'Olá! Tudo bem?\nEstamos entrando em contato para informar que sua invoice já foi liberada para confirmação da declaração aduaneira.\n\nVocê receberá um e-mail com todas as orientações e pode acessar sua conta no site da Braziliana para conferir os detalhes.\n\nÉ o momento de revisar descrições e valores. Se estiver tudo certo, confirme. Caso contrário, clique em “Contestar Invoice”.\n\n⚠ Assim que você confirma, a etiqueta de envio é gerada imediatamente.\nInformações incorretas podem resultar na negação da caixa pela Receita Federal.\n\nPasso a passo no site:\n\nAcesse “Minha Conta”;\n\nVá em “Pedidos”;\n\nClique em “Conferir Invoice”.\n\nConfira se os dados do destinatário (nome, CPF e endereço) estão corretos.\nTambém verifique se as descrições e valores dos produtos batem com suas compras.\n\nUma foto da caixa também é anexada pra conferência.\n\nEstamos à disposição para dúvidas.\n— Equipe Braziliana',
                    'campos' => [],
                ],
            ],
            'Envio e rastreamento' => [
                [
                    'id' => 'hello_envio_confirmado',
                    'titulo' => 'Hello (envio confirmado)',
                    'texto' => 'Hello! 👋\nTemos uma ótima notícia: a etiqueta de envio da sua caixa foi gerada com sucesso!\n\nSeu código de rastreio é: [INSIRA O CÓDIGO]\n\nVocê pode acompanhar aqui:\n👉 https://rastreamento.correios.com.br/app/index.php\n\n⚠ Pode levar de 7 a 10 dias para o status começar a ser atualizado.\n\nAcompanhe também o portal Minhas Importações:\n👉 https://cas.correios.com.br/login?service=https%3A%2F%2Fportalimportador.correios.com.br%2Fpages%2FpesquisarRemessaImportador%2FpesquisarRemessaImportador.jsf\n\n• Prazo médio: 15 a 20 dias úteis\n• A Receita não garante prazo de liberação\n• Produtos sujeitos à ANVISA podem demorar mais\n\n⚠ Importante:\n\nOs Correios não enviam carta ou e-mail sobre impostos.\n\nVerifique o portal regularmente.\n\nO prazo para pagamento dos tributos é de 20 dias corridos.\n\nApós o prazo, a caixa é devolvida aos EUA (processo irreversível).\n\n💰 Se achar o valor dos impostos incorreto, é possível solicitar revisão de tributos no portal.\n\nMuito obrigada por confiar na Braziliana! 💖\n— Fabi',
                    'campos' => [
                        ['chave' => 'INSIRA O CÓDIGO', 'rotulo' => 'Código de rastreio'],
                    ],
                ],
            ],
            'Encerramento / agradecimento' => [
                [
                    'id' => 'encerramento',
                    'titulo' => 'Mensagem de encerramento / agradecimento',
                    'texto' => 'Claro, [Nome]! Caso precise dos nossos serviços no futuro, é só me chamar.\nBoa semana pra você! 🌷',
                    'campos' => [
                        ['chave' => 'Nome', 'rotulo' => 'Nome do cliente'],
                    ],
                ],
            ],
        ];

        foreach ($templates as &$tpls) {
            foreach ($tpls as &$t) {
                foreach ($t['campos'] as &$c) {
                    if ($c['chave'] === 'Nome do Usuario do sistema' && $userName !== '') {
                        $c['valor'] = $userName;
                    }
                }
            }
        }

        $this->render('message_templates/index', [
            'title' => 'Mensagens Padrão',
            'templates' => $templates,
        ]);
    }
}
