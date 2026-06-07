import { Entity, PrimaryGeneratedColumn, Column } from 'typeorm';

@Entity('s_prd', { database: 'producao' })
export class SPrd {
    @PrimaryGeneratedColumn({ name: 'id', type: 'bigint', unsigned: true })
    id: number;

    // Código CNES do prestador — chave de join com prestador.re_cunid
    @Column({ name: 'prd_uid', type: 'varchar', length: 7 })
    prestadorCnes: string;

    @Column({ name: 'prd_cmp', type: 'varchar', length: 6 })
    competence: string;

    @Column({ name: 'prd_pa', type: 'varchar', length: 10 })
    procedureCode: string;

    @Column({ name: 'prd_cbo', type: 'varchar', length: 8, nullable: true })
    cbo: string;

    @Column({ name: 'PRD_QT_P', type: 'int', nullable: true })
    quantityPresented: number;

    @Column({ name: 'PRD_QT_A', type: 'int', nullable: true })
    quantityApproved: number;

    // DECIMAL retorna string no TypeORM — converter no service/frontend
    @Column({ name: 'PRD_VL_P', type: 'decimal', precision: 15, scale: 2, nullable: true })
    valuePresented: string;

    @Column({ name: 'PRD_VL_A', type: 'decimal', precision: 15, scale: 2, nullable: true })
    valueApproved: string;

    @Column({ name: 'prd_rub', type: 'varchar', length: 6, nullable: true })
    financingType: string;

    // Colunas STORED GENERATED — derivadas de prd_pa (apenas leitura, nunca inserir)
    @Column({ name: 'grupo', type: 'varchar', length: 2, nullable: true, insert: false, update: false })
    grupo: string;

    @Column({ name: 'subgrupo', type: 'varchar', length: 4, nullable: true, insert: false, update: false })
    subgrupo: string;

    @Column({ name: 'forma', type: 'varchar', length: 6, nullable: true, insert: false, update: false })
    forma: string;

    @Column({ name: 'PRD_CNPJ', type: 'varchar', length: 14, nullable: true })
    cnpj: string;
}
