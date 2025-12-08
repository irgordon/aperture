import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route, Link } from 'react-router-dom';
import LeadList from './components/LeadList';
import SettingsPage from './components/SettingsPage';
import QuestionnaireBuilder from './components/QuestionnaireBuilder';
import GalleryManager from './components/GalleryManager'; // Import the new manager
import './style.scss';

const AdminApp = () => (
    <HashRouter>
        <div className="ap-layout">
            <nav className="ap-nav">
                <h1>AperturePro</h1>
                <Link to="/">Pipeline</Link>
                <Link to="/galleries">Galleries</Link> {/* NEW TAB */}
                <Link to="/forms">Forms</Link>
                <Link to="/settings">Settings</Link>
            </nav>
            <main>
                <Routes>
                    <Route path="/" element={<LeadList />} />
                    <Route path="/galleries" element={<GalleryManager />} /> {/* NEW ROUTE */}
                    <Route path="/forms" element={<QuestionnaireBuilder />} />
                    <Route path="/settings" element={<SettingsPage />} />
                </Routes>
            </main>
        </div>
    </HashRouter>
);

const root = document.getElementById('aperture-admin');
if(root) createRoot(root).render(<AdminApp />);
