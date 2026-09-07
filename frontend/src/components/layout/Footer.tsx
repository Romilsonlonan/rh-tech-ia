import styles from './Footer.module.css';

export const Footer = () => {
  return (
    <footer className={styles.footer}>
      <div className={styles.container}>
        <div className={styles.grid}>
          <div className={styles.brand}>
            <div className={styles.logo}>
              <span className={styles.logoIcon}>⚡</span>
              <span className={styles.logoText}>RH Tech IA</span>
            </div>
            <p className={styles.description}>
              Transformando recursos humanos com inteligência artificial 
              e tecnologia de ponta.
            </p>
          </div>

          <div className={styles.links}>
            <h4 className={styles.title}>Links Rápidos</h4>
            <ul className={styles.list}>
              <li><a href="#home">Início</a></li>
              <li><a href="#services">Serviços</a></li>
              <li><a href="#about">Sobre</a></li>
              <li><a href="#contact">Contato</a></li>
            </ul>
          </div>

          <div className={styles.links}>
            <h4 className={styles.title}>Serviços</h4>
            <ul className={styles.list}>
              <li><a href="#">Recrutamento IA</a></li>
              <li><a href="#">Treinamentos</a></li>
              <li><a href="#">Consultoria</a></li>
              <li><a href="#">Análise de Currículos</a></li>
            </ul>
          </div>

          <div className={styles.contact}>
            <h4 className={styles.title}>Contato</h4>
            <p className={styles.contactItem}>📧 contato@rhtechia.com</p>
            <p className={styles.contactItem}>📞 (11) 99999-9999</p>
            <p className={styles.contactItem}>📍 São Paulo, SP</p>
          </div>
        </div>

        <div className={styles.bottom}>
          <p>&copy; 2026 RH Tech IA. Todos os direitos reservados.</p>
        </div>
      </div>
    </footer>
  );
};
