# 🥋 Sistema de Gerenciamento de Competições de Jiu-Jitsu

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql)](https://mysql.com)
[![Status](https://img.shields.io/badge/Status-Production-green)](https://github.com/seu-usuario/jiu-jitsu-competitions)

Sistema completo para gestão de competições de Jiu-Jitsu com cadastro de academias, atletas, eventos e processamento de pagamentos integrado.

## 📑 Índice

- [📋 Visão Geral](#-visão-geral)
- [🚀 Funcionalidades](#-funcionalidades-principais)
- [🛠️ Tecnologias](#-tecnologias-utilizadas)
- [📦 Estrutura](#-estrutura-do-projeto)
- [🗄️ Banco de Dados](#-estrutura-do-banco-de-dados)
- [🔐 Segurança](#-segurança)
- [📱 Uso](#-como-utilizar)
- [🤝 Contribuição](#-contribuição)

## 📋 Visão Geral

Sistema completo para gestão de competições de Jiu-Jitsu, permitindo o cadastro de academias, atletas, eventos e processamento de inscrições com integração ao gateway de pagamento Asaas.

## 🚀 Funcionalidades Principais

### 👥 Gestão de Usuários
- **Cadastro de Academias/Equipes**: Sistema de filiação com validação
- **Cadastro de Atletas**: Perfil completo com dados pessoais e esportivos
- **Hierarquia de Responsáveis**: Professores podem gerenciar atletas de suas academias

### 🏆 Gestão de Eventos
- **Criação e Edição**: Eventos com diferentes modalidades (com/sem kimono)
- **Sistema de Preços**: Valores diferenciados por idade e modalidade
- **Eventos Normais**: Modalidade simplificada com preço único
- **Prazos e Limites**: Controle de datas de inscrição e realização

### 💳 Sistema de Pagamentos
- **Integração com Asaas**: Processamento seguro de pagamentos via PIX
- **Status de Pagamento**: Acompanhamento em tempo real
- **Cobranças Recorrentes**: Gestão completa de transações
- **Eventos Gratuitos**: Fluxo diferenciado para isenção de pagamento

### 📊 Gestão de Inscrições
- **Modalidades de Competição**: Sistema completo de categorias por peso
- **Controle de Participação**: Verificação de duplicidade de inscrições
- **Edição e Cancelamento**: Flexibilidade para alterações
- **Termos e Responsabilidades**: Aceite digital de regulamentos

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+ com PDO MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Banco de Dados**: MySQL/MariaDB
- **Pagamentos**: API Asaas (PIX)
- **Segurança**: Password hashing, prevenção contra XSS e SQL injection


## 🗄️ Estrutura do Banco de Dados

### Principais Tabelas:
- **academia_filiada**: Cadastro de academias
- **atleta**: Dados de atletas e responsáveis
- **evento**: Configuração de eventos
- **inscricao**: Relacionamento atleta-eventos
- **galeria**: Conteúdo multimídia

## 🔐 Segurança

- Hash de senhas com algoritmo bcrypt
- Sanitização de inputs contra XSS
- Prevenção contra SQL injection com PDO
- Validação de tipos de arquivo upload
- Controle de sessões e permissões

## 📱 Recursos Avançados

### Para Atletas:
- Perfil pessoal com histórico de competições
- Acompanhamento de status de pagamento
- Edição de dados e modalidades
- Download de editais e regulamentos

### Para Administradores:
- Painel completo de gestão
- Relatórios de inscrições
- Controle financeiro
- Ferramentas de exportação de dados
- Sistema de chapas para organização

## ⚙️ Instalação e Configuração

### Pré-requisitos
- Servidor web (Apache/Nginx)
- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Composer (recomendado)