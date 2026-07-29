import { observer } from 'mobx-react-lite';
import { useEffect, useState } from 'react';
import { OrderFormTab } from './OrderFormTab';
import { OrdersTab } from './OrdersTab';
import { UploadTab } from './UploadTab';
import { templateStore } from '../store/TemplateStore';

type TabId = 'upload' | 'order' | 'orders';

export const HomePage = observer(function HomePage() {
  const [activeTab, setActiveTab] = useState<TabId>('upload');

  useEffect(() => {
    void templateStore.loadTemplate();
  }, []);

  return (
    <div className="container py-4">
      <header className="mb-4">
        <h1 className="h3 mb-1">Excel Transformer</h1>
        <p className="text-muted mb-0">
          Загрузка Excel-шаблона и оформление заказа по прайс-листу
        </p>
      </header>

      {templateStore.error && (
        <div className="alert alert-danger" role="alert">
          {templateStore.error}
        </div>
      )}

      {templateStore.successMessage && (
        <div className="alert alert-success" role="alert">
          {templateStore.successMessage}
        </div>
      )}

      <ul className="nav nav-tabs mb-4">
        <li className="nav-item">
          <button
            type="button"
            className={`nav-link ${activeTab === 'upload' ? 'active' : ''}`}
            onClick={() => setActiveTab('upload')}
          >
            Загрузить шаблон
          </button>
        </li>
        <li className="nav-item">
          <button
            type="button"
            className={`nav-link ${activeTab === 'order' ? 'active' : ''}`}
            onClick={() => setActiveTab('order')}
            disabled={!templateStore.template}
          >
            Заказ по шаблону
          </button>
        </li>
        <li className="nav-item">
          <button
            type="button"
            className={`nav-link ${activeTab === 'orders' ? 'active' : ''}`}
            onClick={() => setActiveTab('orders')}
          >
            Заказы
          </button>
        </li>
      </ul>

      {templateStore.loading ? (
        <div className="text-center py-5 text-muted">Загрузка…</div>
      ) : (
        <>
          {activeTab === 'upload' && (
            <UploadTab onUploaded={() => setActiveTab('order')} />
          )}
          {activeTab === 'order' && (
            <OrderFormTab onSubmitted={() => setActiveTab('orders')} />
          )}
          {activeTab === 'orders' && <OrdersTab />}
        </>
      )}
    </div>
  );
});
