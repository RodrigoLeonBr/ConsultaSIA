import { Entity, Column, PrimaryColumn } from 'typeorm';

@Entity('s_pap', { database: 'producao' })
export class SPap {
    @PrimaryColumn({ name: 'PAP_NUM', type: 'varchar', length: 13 })
    papNum: string;

    @Column({ name: 'PAP_CMP', type: 'varchar', length: 6, nullable: true })
    competence: string;

    @Column({ name: 'PAP_CNPJ', type: 'varchar', length: 14, nullable: true })
    providerId: string;

    @Column({ name: 'PAP_PA', type: 'varchar', length: 10, nullable: true })
    procedureCode: string;

    @Column({ name: 'PAP_QT_A', type: 'double', nullable: true })
    quantityApproved: number;

    @Column({ name: 'PAP_VL_FED', type: 'double', nullable: true })
    federalValue: number;

    @Column({ name: 'PAP_CBO', type: 'varchar', length: 6, nullable: true })
    cbo: string;
}
