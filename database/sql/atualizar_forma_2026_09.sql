-- atualizar_forma_2026_09.sql  (IDEMPOTENTE — re-rodavel em qualquer servidor)
-- Fonte: SIGTAP TabelaUnificada_202609. Adiciona grupos/subgrupos/formas ausentes.
-- id_registro derivado de MAX(id_registro); pula formas ja existentes (NOT EXISTS).
-- Tabela CORE InnoDB: apenas INSERT. Rodar ANTES de procedimento. phpMyAdmin.

SET @base := (SELECT COALESCE(MAX(id_registro),0) FROM forma);
INSERT INTO forma (id_registro, grupo, subgrupo, forma, descricao)
SELECT @base + t.n, t.g, t.s, t.f, t.d
FROM (
  SELECT 1 n,'09' g,'0906' s,'090600' f,'Atencao em Saude Mulher' d
  UNION ALL
  SELECT 2 n,'09' g,'0907' s,'090700' f,'Atencao a Saude Bucal' d
  UNION ALL
  SELECT 3 n,'09' g,'0908' s,'090800' f,'Atencao em Infectologia' d
  UNION ALL
  SELECT 4 n,'03' g,'0301' s,'030116' f,'Atencao Especializada a Pessoa com Falencia Intestinal' d
  UNION ALL
  SELECT 5 n,'03' g,'0307' s,'030705' f,'Estomatologia' d
  UNION ALL
  SELECT 6 n,'03' g,'0309' s,'030908' f,'Terapia Genica' d
  UNION ALL
  SELECT 7 n,'05' g,'0504' s,'050405' f,'Processamento de Membrana Amniotica' d
  UNION ALL
  SELECT 8 n,'06' g,'0603' s,'060309' f,'Terapia Genica' d
  UNION ALL
  SELECT 9 n,'06' g,'0604' s,'060488' f,'Outros Hemostaticos Sistemicos' d
  UNION ALL
  SELECT 10 n,'08' g,'0804' s,'080404' f,'Transplante' d
  UNION ALL
  SELECT 11 n,'09' g,'0906' s,'090601' f,'Ofertas de Cuidados Integrados em Saude da Mulher - Ginecologia' d
  UNION ALL
  SELECT 12 n,'09' g,'0907' s,'090701' f,'Oferta de Cuidado Integral em Saude Bucal' d
  UNION ALL
  SELECT 13 n,'09' g,'0908' s,'090801' f,'Ofertas de Cuidados Integrados em Infectologia' d
) t
LEFT JOIN forma fx ON fx.forma = t.f
WHERE fx.forma IS NULL
ORDER BY t.n;
