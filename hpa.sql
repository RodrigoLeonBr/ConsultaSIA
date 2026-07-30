SELECT 
    pa_num_aih AS AIH,
    pa_cnes AS CNES,
    pa_cmpt AS COMPETENCIA,
    pa_procedimento AS PROC_DETALHADO,
    pa_procedimento_qtd AS QUANTIDADE,
    pa_valor AS VALOR_ITEM,
    pa_financiamento AS FINANCIAMENTO_DETALHE, 
    pa_pf_cbo AS CBO_PROFISSIONAL
FROM 
    TB_HPA
WHERE 
    pa_cmpt = '202601'
ORDER BY 
    pa_num_aih, 
    pa_procedimento;