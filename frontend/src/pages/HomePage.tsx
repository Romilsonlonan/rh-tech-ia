import { Navbar, Footer } from '@/components/layout';
import { HeroSection, ServicesSection } from '@/components/sections';

export const HomePage = () => {
  return (
    <>
      <Navbar />
      <main>
        <HeroSection />
        <ServicesSection />
      </main>
      <Footer />
    </>
  );
};
