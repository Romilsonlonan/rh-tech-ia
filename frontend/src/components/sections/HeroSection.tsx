import { Button } from '@/components/ui';
import styles from './HeroSection.module.css';

export const HeroSection = () => {
  return (
    <section id="home" className={styles.hero}>
      <div className={styles.background}>
        <div className={styles.gradient}></div>
        <div className={styles.grid}></div>
      </div>
      
      <div className={styles.container}>
        <div className={styles.content}>
          <span className={styles.badge}>
            🚀 Transformação Digital em RH
          </span>
          
          <h1 className={styles.title}>
            Revolucione seu RH com
            <span className={styles.highlight}> Inteligência Artificial</span>
          </h1>
          
          <p className={styles.subtitle}>
            Otimize processos de recrutamento, análise de candidatos e 
            gestão de talentos com nossas soluções baseadas em IA.
          </p>
          
          <div className={styles.buttons}>
            <Button variant="primary">Começar Agora</Button>
            <Button variant="secondary">Saiba Mais</Button>
          </div>

          <div className={styles.stats}>
            <div className={styles.stat}>
              <span className={styles.statNumber}>500+</span>
              <span className={styles.statLabel}>Empresas Atendidas</span>
            </div>
            <div className={styles.stat}>
              <span className={styles.statNumber}>10k+</span>
              <span className={styles.statLabel}>Vagas Preenchidas</span>
            </div>
            <div className={styles.stat}>
              <span className={styles.statNumber}>98%</span>
              <span className={styles.statLabel}>Satisfação</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
