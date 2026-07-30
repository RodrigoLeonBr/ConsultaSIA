SELECT 
    h.ah_num_aih AS AIH,
    h.ah_ident AS IDENT_AIH,
    h.ah_cnes AS CNES,
    h.ah_cmpt AS COMPETENCIA,
    h.AH_PACIENTE_LOGR_MUNICIPIO AS MUN_RESIDENCIA, 
    h.ah_paciente_dt_nascimento AS DT_NASC, 
    h.ah_paciente_idade AS IDADE,           
    h.ah_paciente_sexo AS SEXO_PACIENTE,
    h.ah_dt_internacao AS DT_INT,
    h.ah_dt_saida AS DT_SAIDA,
    h.ah_car_internacao AS CARATER_INTERNACAO,
    h.ah_especialidade AS ESPECIALIDADE,
    h.ah_proc_realizado AS PROC_PRINCIPAL,
    h.ah_diag_pri AS DIAG_PRINCIPAL,
    h.ah_diag_sec AS DIAG_SECUNDARIO, 
    h.ah_complexidade AS COMPLEXIDADE,
    h.ah_financiamento AS FINANCIAMENTO,
    h.ah_enfermaria AS ENFERMARIA,
    h.ah_mot_saida AS MOTIVO_SAIDA,
    h.AH_DIAG_OBITO AS CID_OBITO,
    h.ah_diarias AS DIARIAS,
    h.ah_diarias_uti AS DIARIAS_UTI,
        
    (SELECT SUM(hpa.pa_valor) 
     FROM TB_HPA hpa 
     WHERE hpa.pa_seq_princ = h.ah_seq 
       AND hpa.pa_oe_gestor = h.ah_oe_gestor 
       AND hpa.pa_cmpt = h.ah_cmpt) AS VALOR_TOTAL_AIH

FROM 
    TB_HAIH h
WHERE 
    h.ah_situacao = '0'
    AND h.ah_cmpt = '202501'
ORDER BY 
    h.ah_num_aih;