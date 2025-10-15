<?php
// Front controller na raiz para conveniência
// Redireciona ou inclui o front controller real em public/index.php

// Redireciona para a pasta /public (recomendado para servidores web)
header('Location: public/');
exit;
