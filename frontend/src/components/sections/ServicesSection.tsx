import styles from './ServicesSection.module.css';

const services = [
  {
    icon: '🎯',
    title: 'Recrutamento Inteligente',
    description: 'IA que analisa currículos e identifica os melhores candidatos para sua vaga.',
    link: 'Saiba mais →'
  },
  {
    icon: '📚',
    title: 'Treinamentos',
    description: 'Cursos personalizados com gamificação e acompanhamento de progresso.',
    link: 'Saiba mais →'
  },
  {
    icon: '📊',
    title: 'Análise Preditiva',
    description: 'Preveja turnover, engajamento e performance com machine learning.',
    link: 'Saiba mais →'
  },
  {
    icon: '💬',
    title: 'Chatbot RH',
    description: 'Atendimento 24/7 para colaboradores com respostas instantâneas.',
    link: 'Saiba mais →'
  },
  {
    icon: '⚡',
    title: 'Automação',
    description: 'Automatize processos repetitivos e libere tempo para estratégico.',
    link: 'Saiba mais →'
  }
];

export const ServicesSection = () => {
  return (
    <section id="services" className={styles.section}>
      <div className={styles.container}>
        <div className={styles.header}>
          <span className={styles.tag}>NOSSOS SERVIÇOS</span>
          <h2 className={styles.title}>
            Soluções Completas para 
            <span className={styles.highlight}> RH Moderno</span>
          </h2>
          <p className={styles.subtitle}>
            Tecnologias avançadas para transformar sua área de recursos 
            humanos em um motor de crescimento.
          </p>
        </div>

        <div className={styles.grid}>
          {services.map((service, index) => (
            <div key={index} className={styles.card}>
              <div className={styles.iconWrapper}>
                <span className={styles.icon}>{service.icon}</span>
              </div>
              <h3 className={styles.cardTitle}>{service.title}</h3>
              <p className={styles.cardDescription}>{service.description}</p>
              <a href="#" className={styles.cardLink}>{service.link}</a>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
