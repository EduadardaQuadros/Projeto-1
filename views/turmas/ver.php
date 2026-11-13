<h1><?= e($turma['nome']) ?></h1>
<div class="linha-acoes">
    <a class="btn btn-secundario btn-linha" href="<?= e(url('/turmas/' . $turma['id'] . '/editar')) ?>">Editar</a>
</div>
<section class="bloco">
    <dl class="ficha">
        <div><dt>Nível</dt><dd><?= e(NIVEIS[$turma['nivel']] ?? '') ?></dd></div>
        <div><dt>Local</dt><dd><?= e($turma['local'] ?: '—') ?></dd></div>
        <div><dt>Capacidade</dt><dd><?= $turma['capacidade'] === null ? 'Sem limite' : (int) $turma['capacidade'] ?></dd></div>
        <div><dt>Situação</dt><dd><?= (int) $turma['ativa'] ? 'Ativa' : 'Inativa' ?></dd></div>
    </dl>
</section>

<section class="bloco">
    <h2>Horários</h2>
    <?php if ($horarios === []): ?>
        <p class="vazio">Nenhum horário cadastrado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($horarios as $horario): ?>
                <li>
                    <span><?= e(DIAS[(int) $horario['dia_semana']] ?? '') ?> · <?= e(formatar_hora($horario['hora_inicio'])) ?>–<?= e(formatar_hora($horario['hora_fim'])) ?></span>
                    <form method="post" action="<?= e(url('/horarios/' . $horario['id'] . '/excluir')) ?>" onsubmit="return confirm('Remover este horário?');">
                        <?= csrf_field() ?>
                        <button class="btn-perigo" type="submit">Remover</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/turmas/' . $turma['id'] . '/horarios')) ?>" class="form">
        <?= csrf_field() ?>
        <label for="dia_semana">Dia</label>
        <select id="dia_semana" name="dia_semana" required>
            <option value="">Selecione</option>
            <?php foreach (DIAS as $valor => $rotulo): ?>
                <option value="<?= (int) $valor ?>"><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="hora_inicio">Início</label>
        <input id="hora_inicio" name="hora_inicio" type="time" required>
        <label for="hora_fim">Fim</label>
        <input id="hora_fim" name="hora_fim" type="time" required>
        <button class="btn" type="submit">Adicionar horário</button>
    </form>
</section>

<section class="bloco">
    <h2>Professores</h2>
    <?php if ($professores === []): ?>
        <p class="vazio">Nenhum professor vinculado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($professores as $professor): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/professores/' . $professor['id'])) ?>"><?= e($professor['nome']) ?></a>
                        <?php if (!(int) $professor['ativo']): ?><span class="selo selo-inativo">Inativo</span><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('/turmas/' . $turma['id'] . '/professores/' . $professor['id'] . '/remover')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn-perigo" type="submit">Tirar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($professores_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/turmas/' . $turma['id'] . '/professores')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="professor_id">Incluir professor</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">Selecione</option>
                <?php foreach ($professores_disponiveis as $professor): ?>
                    <option value="<?= (int) $professor['id'] ?>"><?= e($professor['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Vincular</button>
        </form>
    <?php endif; ?>
</section>

<section class="bloco">
    <h2>Alunos matriculados</h2>
    <?php if ($matriculas === []): ?>
        <p class="vazio">Nenhum aluno matriculado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($matriculas as $matricula): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/alunos/' . $matricula['aluno_id'])) ?>"><?= e($matricula['nome']) ?></a>
                        <?php if (eh_menor($matricula['data_nascimento'])): ?><span class="selo">Menor</span><?php endif; ?>
                        <small><?= e(formatar_data($matricula['data_inicio'])) ?></small>
                    </span>
                    <form method="post" action="<?= e(url('/matriculas/' . $matricula['id'] . '/encerrar')) ?>" onsubmit="return confirm('Encerrar esta matrícula?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="voltar" value="<?= e('/turmas/' . $turma['id']) ?>">
                        <button class="btn-perigo" type="submit">Encerrar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ((int) $turma['ativa'] && $alunos_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/turmas/' . $turma['id'] . '/matriculas')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="aluno_id">Matricular aluno</label>
            <select id="aluno_id" name="aluno_id" required>
                <option value="">Selecione</option>
                <?php foreach ($alunos_disponiveis as $aluno): ?>
                    <option value="<?= (int) $aluno['id'] ?>"><?= e($aluno['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="data_inicio">Início</label>
            <input id="data_inicio" name="data_inicio" type="date" required value="<?= e(hoje()) ?>">
            <button class="btn" type="submit">Matricular</button>
        </form>
    <?php elseif (!(int) $turma['ativa']): ?>
        <p class="vazio">Reative a turma para matricular alunos.</p>
    <?php endif; ?>
    <?php if ($encerradas !== []): ?>
        <h2>Matrículas encerradas</h2>
        <ul class="itens">
            <?php foreach ($encerradas as $matricula): ?>
                <li>
                    <span><?= e($matricula['nome']) ?> · até <?= e(formatar_data($matricula['data_fim'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
