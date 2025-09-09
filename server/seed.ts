import { db } from "./db";
import { storage } from "./storage";
import { users, cbo, prestador, procedimento, sRub } from "@shared/schema";

async function seedDatabase() {
  console.log("🌱 Starting database seeding...");

  try {
    // Create admin user
    console.log("👤 Creating default admin user...");
    const adminUser = await storage.createUser({
      username: "admin",
      password: "admin123",
      email: "admin@consultaprod.com",
      firstName: "Admin",
      lastName: "Sistema",
      role: "admin",
      active: true,
    });
    console.log("✅ Admin user created:", adminUser.username);

    // Create sample CBO records
    console.log("💼 Creating sample CBO records...");
    const cboRecords = [
      { codigo: "225125", descricao: "Médico clínico", status: true },
      { codigo: "225133", descricao: "Médico em medicina de família e comunidade", status: true },
      { codigo: "225170", descricao: "Médico ginecologista e obstetra", status: true },
      { codigo: "223505", descricao: "Enfermeiro", status: true },
      { codigo: "322205", descricao: "Técnico de enfermagem", status: true },
    ];

    for (const cboData of cboRecords) {
      await storage.createCBO(cboData);
    }
    console.log(`✅ Created ${cboRecords.length} CBO records`);

    // Create sample prestadores
    console.log("🏥 Creating sample prestadores...");
    const prestadorRecords = [
      {
        codigo: "001",
        nomeRazaoSocial: "Hospital Municipal São José",
        cnpjCpf: "12.345.678/0001-90",
        tipo: "pessoa_juridica",
        status: true,
      },
      {
        codigo: "002", 
        nomeRazaoSocial: "Clínica Santa Maria",
        cnpjCpf: "98.765.432/0001-10",
        tipo: "pessoa_juridica",
        status: true,
      },
      {
        codigo: "003",
        nomeRazaoSocial: "Dr. João Silva",
        cnpjCpf: "123.456.789-01",
        tipo: "pessoa_fisica",
        status: true,
      },
    ];

    for (const prestadorData of prestadorRecords) {
      await storage.createPrestador(prestadorData);
    }
    console.log(`✅ Created ${prestadorRecords.length} prestador records`);

    // Create sample procedimentos
    console.log("🔬 Creating sample procedimentos...");
    const procedimentoRecords = [
      {
        codigo: "03.01.01.007-2",
        descricao: "Consulta médica em atenção básica",
        valor: "10.00",
        complexidade: "baixa",
        status: true,
      },
      {
        codigo: "02.05.02.007-0",
        descricao: "Radiografia de tórax",
        valor: "15.50",
        complexidade: "media",
        status: true,
      },
      {
        codigo: "04.03.02.018-6",
        descricao: "Cirurgia de apendicectomia",
        valor: "850.00",
        complexidade: "alta",
        status: true,
      },
    ];

    for (const procedimentoData of procedimentoRecords) {
      await storage.createProcedimento(procedimentoData);
    }
    console.log(`✅ Created ${procedimentoRecords.length} procedimento records`);

    // Create sample S_RUB records
    console.log("💰 Creating sample S_RUB records...");
    const srubRecords = [
      {
        codigo: "MAC001",
        descricao: "Média e Alta Complexidade",
        tipoFinanciamento: "Federal",
        status: true,
      },
      {
        codigo: "PAB001",
        descricao: "Piso de Atenção Básica",
        tipoFinanciamento: "Municipal",
        status: true,
      },
    ];

    for (const srubData of srubRecords) {
      await storage.createSRub(srubData);
    }
    console.log(`✅ Created ${srubRecords.length} S_RUB records`);

    console.log("🎉 Database seeding completed successfully!");
    console.log("");
    console.log("📋 Login credentials:");
    console.log("   Username: admin");
    console.log("   Password: admin123");
    console.log("");

  } catch (error) {
    console.error("❌ Error seeding database:", error);
    throw error;
  }
}

// Run seeding if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
  seedDatabase()
    .then(() => {
      console.log("✅ Seeding completed");
      process.exit(0);
    })
    .catch((error) => {
      console.error("❌ Seeding failed:", error);
      process.exit(1);
    });
}

export { seedDatabase };